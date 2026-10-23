import Foundation
import SwiftUI
import Observation

/// Master State Store & Hybrid Online/Offline Simulation Engine for Ski Manager iOS
/// Uses Swift 6 Observation framework (`@Observable`) and isolated concurrency.
@Observable
@MainActor
public final class ResortStore {
    // MARK: - Core State
    
    public var resort: Resort
    public var selectedSector: Int = 1
    
    // Online & Cloud Sync State
    public var isOnline: Bool = true
    public var isCloudSyncing: Bool = false
    public var connectionStatusText: String = "Connecting to ski-manager.net..."
    public var lastSyncDate: Date? = nil
    public var cloudSyncError: String? = nil
    public var cloudAccountUsername: String? = nil
    public var onlineLeaderboard: [LeaderboardEntry] = []
    public var lastBonusAwardMessage: String? = nil
    
    // MARK: - Persistence Keys
    
    private let saveFileName = "ski_manager_resort_v4.json"
    
    // MARK: - Initialization
    
    public init() {
        if let savedResort = Self.loadFromDisk(filename: "ski_manager_resort_v4.json") {
            self.resort = savedResort
        } else {
            self.resort = Resort.makeParkCityTemplate()
        }
        
        // Auto connect and sync on app launch
        Task { [weak self] in
            await self?.performInitialOnlineConnection()
        }
    }
    
    // MARK: - Initial Online Connection & Telemetry Pull
    
    public func performInitialOnlineConnection() async {
        do {
            let health = try await SkiManagerAPIClient.shared.checkServerHealth()
            isOnline = health.isHealthy
            connectionStatusText = health.message
            
            // Fetch online leaderboard
            if let board = try? await SkiManagerAPIClient.shared.fetchLeaderboard() {
                self.onlineLeaderboard = board
            }
            
            // Sync resort baseline if online
            if isOnline {
                _ = await syncWithLiveWebsite()
            }
        } catch {
            isOnline = false
            connectionStatusText = "Operating Offline (Cached Data)"
        }
    }
    
    // MARK: - Simulation Day Progression ("Advance Game Day")
    
    public func advanceDay() {
        // 1. Increment Game Day
        resort.gameDay += 1
        
        // 2. Weather Dynamics
        updateMountainWeather()
        
        // 3. Visitor Attendance Equation matching website algorithm
        let priceFactor = max(0.5, 1.6 - (Double(resort.ticketPriceAdult) / 68.0) * 0.6)
        let openSlopesFactor = Double(resort.openSlopesCount) / Double(max(1, resort.openSlopesCount + 2))
        let snowQualityFactor = Double(resort.weather.newSnowfall24hCm > 10 ? 1.25 : 1.0)
        let repFactor = Double(resort.reputationScore) / 100.0
        
        let baseVisitors = Double.random(in: 420...680)
        let calculatedVisitors = Int(baseVisitors * priceFactor * openSlopesFactor * snowQualityFactor * repFactor * resort.difficulty.visitorMultiplier)
        resort.totalVisitorsToday = max(180, calculatedVisitors)
        
        // 4. Financial Calculations
        let ticketRev = resort.dailyTicketRevenue
        let lodgeRev = resort.dailyLodgeRevenue
        let staffCost = resort.dailyStaffExpenses
        let liftMaint = resort.dailyLiftMaintenance
        let slopeUpkeep = resort.dailySlopeUpkeep
        let loanInterest = resort.dailyLoanInterest
        
        // Apply cash delta
        let dayGross = ticketRev + lodgeRev
        let dayExpenses = staffCost + liftMaint + slopeUpkeep + loanInterest
        let netProfit = dayGross - dayExpenses
        
        resort.cash += netProfit
        
        // 5. Append Ledger Records
        var newTx: [GameTransaction] = []
        newTx.append(GameTransaction(title: "Lift Ticket Sales (Day \(resort.gameDay))", amount: ticketRev, category: .ticketSales, gameDay: resort.gameDay))
        if lodgeRev > 0 {
            newTx.append(GameTransaction(title: "Lodge & Dining Receipts", amount: lodgeRev, category: .buildingRevenue, gameDay: resort.gameDay))
        }
        newTx.append(GameTransaction(title: "Staff Payroll Deductions", amount: -staffCost, category: .staffWages, gameDay: resort.gameDay))
        newTx.append(GameTransaction(title: "Lift Fleet Electricity & Maintenance", amount: -liftMaint, category: .liftMaintenance, gameDay: resort.gameDay))
        if slopeUpkeep > 0 {
            newTx.append(GameTransaction(title: "Slope Grooming Upkeep", amount: -slopeUpkeep, category: .slopeMaintenance, gameDay: resort.gameDay))
        }
        if loanInterest > 0 {
            newTx.append(GameTransaction(title: "Bank Loan Interest", amount: -loanInterest, category: .bankInterest, gameDay: resort.gameDay))
        }
        
        // Prepend and cap at last 50 transactions
        resort.transactions = (newTx + resort.transactions).prefix(50).map { $0 }
        
        // 6. Wear & Tear on Real Map Lifts
        for i in resort.realSegments.indices where resort.realSegments[i].isLift && resort.realSegments[i].isBuilt {
            if resort.realSegments[i].isOpen {
                let wear = Int.random(in: 1...3)
                resort.realSegments[i].conditionPercent = max(25, resort.realSegments[i].conditionPercent - wear)
                let mechanicCount = resort.staff.filter { $0.role == .mechanic }.count
                if mechanicCount > 0 {
                    resort.realSegments[i].conditionPercent = min(100, resort.realSegments[i].conditionPercent + (mechanicCount * 2))
                }
            }
        }
        
        // 7. Slope Snow Compaction & Grooming Decay
        for i in resort.realSegments.indices where resort.realSegments[i].isSlope && resort.realSegments[i].isBuilt {
            if resort.realSegments[i].isOpen {
                let decay = Int.random(in: 8...16)
                resort.realSegments[i].groomingPercent = max(15, resort.realSegments[i].groomingPercent - decay)
            }
        }
        
        // 8. Groomer Durability Degradation
        resort.groomerConditionPct = max(20, resort.groomerConditionPct - Int.random(in: 1...3))
        
        // 9. Staff Morale Dynamics
        for i in resort.staff.indices {
            let moraleDelta = netProfit > 0 ? Int.random(in: 0...2) : -Int.random(in: 1...3)
            resort.staff[i].moralePercent = max(25, min(100, resort.staff[i].moralePercent + moraleDelta))
        }
        
        // 10. Save Snapshot to Local Disk
        saveToDisk()
        
        // 11. Background Push to ski-manager.net
        triggerBackgroundPush()
    }
    
    // MARK: - Daily Bonus Engine
    
    @discardableResult
    public func claimDailyBonus() -> (cash: Int, genepis: Int)? {
        guard resort.canClaimDailyBonus else { return nil }
        
        let streak = resort.dailyBonusStreak
        let cashReward = 5000 + (streak * 750)
        let genepisReward = 5 + min(10, streak)
        
        resort.cash += cashReward
        resort.genepis += genepisReward
        resort.lastBonusClaimDay = resort.gameDay
        resort.dailyBonusStreak += 1
        
        resort.transactions.insert(
            GameTransaction(
                title: "Daily Bonus Streak Day #\(streak)",
                amount: cashReward,
                category: .ticketSales,
                gameDay: resort.gameDay
            ),
            at: 0
        )
        
        lastBonusAwardMessage = "Claimed +€\(cashReward.formatted()) and +\(genepisReward) 🌿 Génépis!"
        saveToDisk()
        triggerBackgroundPush()
        return (cashReward, genepisReward)
    }
    
    // MARK: - Weather Engine
    
    private func updateMountainWeather() {
        let conditions: [WeatherCondition] = [
            .bluebirdPowder, .lightSnow, .sunny, .partlyCloudy, .heavyBlizzard, .lightSnow, .bluebirdPowder
        ]
        let nextCondition = conditions.randomElement() ?? .lightSnow
        let snowAccum: Int
        let windKmh: Int
        let dangerLevel: Int
        
        switch nextCondition {
        case .heavyBlizzard:
            snowAccum = Int.random(in: 25...45)
            windKmh = Int.random(in: 45...70)
            dangerLevel = 4
        case .bluebirdPowder:
            snowAccum = Int.random(in: 15...30)
            windKmh = Int.random(in: 10...22)
            dangerLevel = 3
        case .lightSnow:
            snowAccum = Int.random(in: 5...14)
            windKmh = Int.random(in: 12...28)
            dangerLevel = 2
        case .sunny, .partlyCloudy:
            snowAccum = 0
            windKmh = Int.random(in: 8...20)
            dangerLevel = 1
        case .denseFog:
            snowAccum = Int.random(in: 2...6)
            windKmh = Int.random(in: 5...15)
            dangerLevel = 2
        case .springSlush:
            snowAccum = 0
            windKmh = Int.random(in: 10...25)
            dangerLevel = 1
        }
        
        let newTemp = nextCondition == .sunny ? Double.random(in: -2.0...2.0) : Double.random(in: -9.0 ... -3.0)
        let newBase = max(60, resort.weather.baseDepthCm + snowAccum - (newTemp > 0 ? 3 : 1))
        
        resort.weather = MountainWeather(
            condition: nextCondition,
            temperatureCelsius: newTemp,
            newSnowfall24hCm: snowAccum,
            baseDepthCm: newBase,
            windSpeedKmh: windKmh,
            visibilityKm: nextCondition == .denseFog ? 1.5 : (nextCondition == .heavyBlizzard ? 3.0 : 15.0),
            avalancheDangerLevel: dangerLevel,
            isSnowmakingOptimal: newTemp <= -3.0,
            forecast7Day: MountainWeather.generateDefaultForecast()
        )
    }
    
    // MARK: - Real Map Segment Operations
    
    public func toggleRealSegmentOpen(id: Int) {
        guard let index = resort.realSegments.firstIndex(where: { $0.id == id }) else { return }
        resort.realSegments[index].isOpen.toggle()
        saveToDisk()
        triggerBackgroundPush()
    }
    
    public func buildRealSegment(id: Int) -> Bool {
        guard let index = resort.realSegments.firstIndex(where: { $0.id == id }) else { return false }
        let seg = resort.realSegments[index]
        guard !seg.isBuilt else { return true }
        
        let cost = seg.constructionCost
        guard resort.cash >= cost else { return false }
        
        resort.cash -= cost
        resort.realSegments[index].isBuilt = true
        resort.realSegments[index].isOpen = true
        
        let txTitle = seg.isLift ? "Constructed \(seg.name)" : "Carved \(seg.name) Trail"
        resort.transactions.insert(
            GameTransaction(title: txTitle, amount: -cost, category: .capitalInvestment, gameDay: resort.gameDay),
            at: 0
        )
        saveToDisk()
        triggerBackgroundPush()
        return true
    }
    
    // MARK: - Live Website Data Sync
    
    public func syncWithLiveWebsite() async -> Bool {
        isCloudSyncing = true
        cloudSyncError = nil
        
        do {
            let apiData = try await SkiManagerAPIClient.shared.fetchResortData()
            
            // Sync server values
            if let serverFin = apiData.finances {
                resort.cash = serverFin.cash ?? resort.cash
                resort.totalVisitorsToday = serverFin.daily_visitors ?? resort.totalVisitorsToday
            }
            if let serverWeather = apiData.weather {
                resort.gameDay = serverWeather.game_day ?? resort.gameDay
                resort.weather.temperatureCelsius = Double(serverWeather.temperature ?? -4)
                resort.weather.baseDepthCm = serverWeather.base_depth ?? resort.weather.baseDepthCm
                resort.weather.newSnowfall24hCm = serverWeather.snowfall ?? resort.weather.newSnowfall24hCm
                resort.weather.windSpeedKmh = serverWeather.wind_speed ?? resort.weather.windSpeedKmh
            }
            
            // Sync real segments from server if provided
            if let apiSegments = apiData.segments, !apiSegments.isEmpty {
                for apiSeg in apiSegments {
                    if let idx = resort.realSegments.firstIndex(where: { $0.id == apiSeg.id }) {
                        resort.realSegments[idx].name = apiSeg.name
                        resort.realSegments[idx].difficulty = apiSeg.difficulty
                        resort.realSegments[idx].points = apiSeg.points
                        resort.realSegments[idx].lengthMeters = apiSeg.length_meters ?? resort.realSegments[idx].lengthMeters
                    }
                }
            }
            
            // Refresh leaderboard
            if let board = try? await SkiManagerAPIClient.shared.fetchLeaderboard() {
                self.onlineLeaderboard = board
            }
            
            lastSyncDate = Date()
            isOnline = true
            connectionStatusText = "Online · Connected to ski-manager.net"
            isCloudSyncing = false
            saveToDisk()
            return true
        } catch {
            cloudSyncError = error.localizedDescription
            isOnline = false
            connectionStatusText = "Offline (Cached Data)"
            isCloudSyncing = false
            return false
        }
    }
    
    // Background push telemetry to server without blocking UI
    private func triggerBackgroundPush() {
        Task { [weak self] in
            guard let self = self else { return }
            let payload = ClientSyncPayload(
                cash: self.resort.cash,
                game_day: self.resort.gameDay,
                daily_visitors: self.resort.totalVisitorsToday,
                ticket_price_adult: self.resort.ticketPriceAdult,
                ticket_price_child: self.resort.ticketPriceChild,
                built_segments_count: self.resort.realSegments.filter { $0.isBuilt }.count,
                outstanding_loan: self.resort.outstandingLoan
            )
            do {
                _ = try await SkiManagerAPIClient.shared.pushSync(payload: payload)
                await MainActor.run {
                    self.isOnline = true
                    self.connectionStatusText = "Online · Synced"
                    self.lastSyncDate = Date()
                }
            } catch {
                // Keep local changes seamlessly
            }
        }
    }
    
    // MARK: - Mountain Maintenance Actions
    
    public func groomAllSlopes() {
        let groomCost = resort.realSegments.filter { $0.isSlope && $0.isBuilt }.count * 85
        guard resort.cash >= groomCost, groomCost > 0 else { return }
        
        resort.cash -= groomCost
        resort.transactions.insert(
            GameTransaction(title: "PistenBully 600 Corduroy Grooming Fleet", amount: -groomCost, category: .slopeMaintenance, gameDay: resort.gameDay),
            at: 0
        )
        
        for i in resort.realSegments.indices where resort.realSegments[i].isSlope && resort.realSegments[i].isBuilt {
            resort.realSegments[i].groomingPercent = 95
        }
        for i in resort.slopes.indices {
            resort.slopes[i].groomingPercent = 95
            resort.slopes[i].snowQuality = .groomedCorduroy
        }
        resort.groomerConditionPct = max(20, resort.groomerConditionPct - 2)
        saveToDisk()
        triggerBackgroundPush()
    }
    
    public func makeSnowOnEquippedSlopes() {
        guard resort.weather.isSnowmakingOptimal else { return }
        let snowmakingCost = 2500
        guard resort.cash >= snowmakingCost else { return }
        
        resort.cash -= snowmakingCost
        resort.transactions.insert(
            GameTransaction(title: "TechnoAlpin Snowmaking Water & Energy Draw", amount: -snowmakingCost, category: .snowmakingEnergy, gameDay: resort.gameDay),
            at: 0
        )
        
        for i in resort.slopes.indices where resort.slopes[i].snowmakingEquipped {
            resort.slopes[i].snowDepthCm += 15
            resort.slopes[i].snowQuality = .packedPowder
        }
        resort.weather.baseDepthCm += 6
        saveToDisk()
        triggerBackgroundPush()
    }
    
    public func repairGroomer() -> Bool {
        let cost = 3200
        guard resort.cash >= cost, resort.groomerConditionPct < 100 else { return false }
        resort.cash -= cost
        resort.groomerConditionPct = 100
        resort.transactions.insert(
            GameTransaction(title: "PistenBully 600 Factory Overhaul", amount: -cost, category: .slopeMaintenance, gameDay: resort.gameDay),
            at: 0
        )
        saveToDisk()
        triggerBackgroundPush()
        return true
    }
    
    public func repairLift(id: UUID) {
        guard let idx = resort.lifts.firstIndex(where: { $0.id == id }) else { return }
        let cost = 2400
        guard resort.cash >= cost else { return }
        resort.cash -= cost
        resort.lifts[idx].conditionPercent = 100
        resort.transactions.insert(
            GameTransaction(title: "Mechanical Overhaul: \(resort.lifts[idx].name)", amount: -cost, category: .liftMaintenance, gameDay: resort.gameDay),
            at: 0
        )
        saveToDisk()
        triggerBackgroundPush()
    }
    
    public func boostStaffMorale(id: UUID) {
        guard let idx = resort.staff.firstIndex(where: { $0.id == id }) else { return }
        let cost = 350
        guard resort.cash >= cost else { return }
        resort.cash -= cost
        resort.staff[idx].moralePercent = min(100, resort.staff[idx].moralePercent + 20)
        resort.transactions.insert(
            GameTransaction(title: "Director Spot Bonus: \(resort.staff[idx].name)", amount: -cost, category: .staffWages, gameDay: resort.gameDay),
            at: 0
        )
        saveToDisk()
        triggerBackgroundPush()
    }
    
    // MARK: - Mountain Builder Actions
    
    public func buildLift(name: String, type: LiftType, sector: Int, length: Int, rise: Int) -> Bool {
        let cost = type.baseConstructionCost
        guard resort.cash >= cost else { return false }
        
        resort.cash -= cost
        let newLift = Lift(
            name: name,
            liftType: type,
            sectorNumber: sector,
            lengthMeters: length,
            verticalRiseMeters: rise,
            status: .running,
            conditionPercent: 100
        )
        resort.lifts.append(newLift)
        resort.transactions.insert(
            GameTransaction(title: "Constructed \(name) (\(type.rawValue))", amount: -cost, category: .capitalInvestment, gameDay: resort.gameDay),
            at: 0
        )
        saveToDisk()
        triggerBackgroundPush()
        return true
    }
    
    public func cutSlope(name: String, difficulty: SlopeDifficulty, sector: Int, length: Int, verticalDrop: Int, snowmaking: Bool, nightSkiing: Bool) -> Bool {
        let cost = difficulty.baseCutCost + (snowmaking ? 20000 : 0) + (nightSkiing ? 15000 : 0)
        guard resort.cash >= cost else { return false }
        
        resort.cash -= cost
        let newSlope = Slope(
            name: name,
            difficulty: difficulty,
            sectorNumber: sector,
            lengthMeters: length,
            verticalDropMeters: verticalDrop,
            groomingPercent: 90,
            snowmakingEquipped: snowmaking,
            nightSkiingLighted: nightSkiing,
            isOpen: true,
            snowDepthCm: resort.weather.baseDepthCm,
            snowQuality: .groomedCorduroy
        )
        resort.slopes.append(newSlope)
        resort.transactions.insert(
            GameTransaction(title: "Carved \(name) Trail (\(difficulty.shortLabel))", amount: -cost, category: .capitalInvestment, gameDay: resort.gameDay),
            at: 0
        )
        saveToDisk()
        triggerBackgroundPush()
        return true
    }
    
    public func constructBuilding(name: String, type: BuildingType, sector: Int) -> Bool {
        let cost = type.baseBuildCost
        guard resort.cash >= cost else { return false }
        
        resort.cash -= cost
        let building = ResortBuilding(name: name, buildingType: type, upgradeLevel: 1, isOpen: true, sectorNumber: sector)
        resort.buildings.append(building)
        resort.transactions.insert(
            GameTransaction(title: "Built \(name) Facility", amount: -cost, category: .capitalInvestment, gameDay: resort.gameDay),
            at: 0
        )
        saveToDisk()
        triggerBackgroundPush()
        return true
    }
    
    public func upgradeBuilding(id: UUID) -> Bool {
        guard let idx = resort.buildings.firstIndex(where: { $0.id == id }) else { return false }
        let cost = resort.buildings[idx].nextUpgradeCost
        guard resort.cash >= cost, resort.buildings[idx].upgradeLevel < 5 else { return false }
        
        resort.cash -= cost
        resort.buildings[idx].upgradeLevel += 1
        resort.transactions.insert(
            GameTransaction(title: "Upgraded \(resort.buildings[idx].name) to Level \(resort.buildings[idx].upgradeLevel)", amount: -cost, category: .capitalInvestment, gameDay: resort.gameDay),
            at: 0
        )
        saveToDisk()
        triggerBackgroundPush()
        return true
    }
    
    public func toggleBuildingOpen(id: UUID) {
        guard let idx = resort.buildings.firstIndex(where: { $0.id == id }) else { return }
        resort.buildings[idx].isOpen.toggle()
        saveToDisk()
        triggerBackgroundPush()
    }
    
    public func toggleLiftStatus(id: UUID) {
        guard let index = resort.lifts.firstIndex(where: { $0.id == id }) else { return }
        resort.lifts[index].status = resort.lifts[index].status == .running ? .closed : .running
        saveToDisk()
        triggerBackgroundPush()
    }
    
    public func toggleSlopeStatus(id: UUID) {
        guard let index = resort.slopes.firstIndex(where: { $0.id == id }) else { return }
        resort.slopes[index].isOpen.toggle()
        saveToDisk()
        triggerBackgroundPush()
    }
    
    public func hireStaff(name: String, role: StaffRole) -> Bool {
        let member = StaffMember(name: name, role: role, skillStars: Int.random(in: 3...5), moralePercent: 90)
        resort.staff.append(member)
        saveToDisk()
        triggerBackgroundPush()
        return true
    }
    
    public func fireStaff(id: UUID) {
        resort.staff.removeAll(where: { $0.id == id })
        saveToDisk()
        triggerBackgroundPush()
    }
    
    public func takeLoan(amount: Int) {
        resort.outstandingLoan += amount
        resort.cash += amount
        resort.transactions.insert(
            GameTransaction(title: "Park City Commercial Bank Loan Disbursed", amount: amount, category: .capitalInvestment, gameDay: resort.gameDay),
            at: 0
        )
        saveToDisk()
        triggerBackgroundPush()
    }
    
    public func repayLoan(amount: Int) -> Bool {
        let repayAmount = min(amount, resort.outstandingLoan)
        guard resort.cash >= repayAmount, repayAmount > 0 else { return false }
        
        resort.cash -= repayAmount
        resort.outstandingLoan -= repayAmount
        resort.transactions.insert(
            GameTransaction(title: "Bank Loan Principal Repayment", amount: -repayAmount, category: .capitalInvestment, gameDay: resort.gameDay),
            at: 0
        )
        saveToDisk()
        triggerBackgroundPush()
        return true
    }
    
    public func setTicketPrices(adult: Int, child: Int) {
        resort.ticketPriceAdult = max(20, min(250, adult))
        resort.ticketPriceChild = max(10, min(150, child))
        saveToDisk()
        triggerBackgroundPush()
    }
    
    public func resetToTemplate() {
        self.resort = Resort.makeParkCityTemplate()
        saveToDisk()
        triggerBackgroundPush()
    }
    
    // MARK: - Local Disk Persistence Engine
    
    public func saveToDisk() {
        guard let url = Self.documentsURL?.appendingPathComponent(saveFileName) else { return }
        do {
            let encoder = JSONEncoder()
            encoder.outputFormatting = .prettyPrinted
            encoder.dateEncodingStrategy = .iso8601
            let data = try encoder.encode(resort)
            try data.write(to: url, options: [.atomic, .completeFileProtection])
        } catch {
            print("[ResortStore] Error saving resort to disk: \(error)")
        }
    }
    
    private static func loadFromDisk(filename: String) -> Resort? {
        guard let url = documentsURL?.appendingPathComponent(filename),
              FileManager.default.fileExists(atPath: url.path) else { return nil }
        do {
            let data = try Data(contentsOf: url)
            let decoder = JSONDecoder()
            decoder.dateDecodingStrategy = .iso8601
            return try decoder.decode(Resort.self, from: data)
        } catch {
            print("[ResortStore] Error loading resort from disk: \(error)")
            return nil
        }
    }
    
    public static var localSaveFileSizeFormatted: String {
        guard let url = documentsURL?.appendingPathComponent("ski_manager_resort_v4.json"),
              let attrs = try? FileManager.default.attributesOfItem(atPath: url.path),
              let size = attrs[.size] as? Int64 else {
            return "64 KB (Bundled)"
        }
        return ByteCountFormatter.string(fromByteCount: size, countStyle: .file)
    }
    
    public static var documentsURL: URL? {
        FileManager.default.urls(for: .documentDirectory, in: .userDomainMask).first
    }
}
