import Foundation
import SwiftUI
import Observation

/// Master State Store & Offline Simulation Engine for Ski Manager iOS
/// Uses Swift 6 Observation framework (`@Observable`) and isolated concurrency.
@Observable
@MainActor
public final class ResortStore {
    // MARK: - Core State
    
    public var resort: Resort
    public var selectedSector: Int = 1
    public var isCloudSyncing: Bool = false
    public var lastSyncDate: Date? = nil
    public var cloudSyncError: String? = nil
    public var cloudAccountUsername: String? = nil
    public var isSimulationRunning: Bool = false
    
    // MARK: - Persistence Keys
    
    private let saveFileName = "ski_manager_resort_v2.json"
    
    // MARK: - Initialization
    
    public init() {
        if let savedResort = Self.loadFromDisk(filename: "ski_manager_resort_v2.json") {
            self.resort = savedResort
        } else {
            self.resort = Resort.makeParkCityTemplate()
        }
    }
    
    // MARK: - Simulation Day Progression ("Advance Game Day")
    
    public func advanceDay() {
        // 1. Increment Game Day
        resort.gameDay += 1
        
        // 2. Weather Dynamics
        updateMountainWeather()
        
        // 3. Visitor Attendance Equation
        let priceFactor = max(0.5, 1.6 - (Double(resort.ticketPriceAdult) / 85.0) * 0.6)
        let openSlopesFactor = Double(resort.openSlopesCount) / Double(max(1, resort.slopes.count))
        let snowQualityFactor = Double(resort.weather.newSnowfall24hCm > 10 ? 1.25 : 1.0)
        let repFactor = Double(resort.reputationScore) / 100.0
        
        let baseVisitors = Double.random(in: 1800...3200)
        let calculatedVisitors = Int(baseVisitors * priceFactor * openSlopesFactor * snowQualityFactor * repFactor * resort.difficulty.visitorMultiplier)
        resort.totalVisitorsToday = max(250, calculatedVisitors)
        
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
        newTx.append(GameTransaction(title: "Lift Tickets (Day \(resort.gameDay))", amount: ticketRev, category: .ticketSales, gameDay: resort.gameDay))
        if lodgeRev > 0 {
            newTx.append(GameTransaction(title: "Lodge & Dining Receipts", amount: lodgeRev, category: .buildingRevenue, gameDay: resort.gameDay))
        }
        newTx.append(GameTransaction(title: "Staff Salaries", amount: -staffCost, category: .staffWages, gameDay: resort.gameDay))
        newTx.append(GameTransaction(title: "Lift Fleet Maintenance", amount: -liftMaint, category: .liftMaintenance, gameDay: resort.gameDay))
        if slopeUpkeep > 0 {
            newTx.append(GameTransaction(title: "Slope Grooming Upkeep", amount: -slopeUpkeep, category: .slopeMaintenance, gameDay: resort.gameDay))
        }
        if loanInterest > 0 {
            newTx.append(GameTransaction(title: "Bank Loan Interest", amount: -loanInterest, category: .bankInterest, gameDay: resort.gameDay))
        }
        
        // Prepend and cap at last 50 transactions
        resort.transactions = (newTx + resort.transactions).prefix(50).map { $0 }
        
        // 6. Wear & Tear on Lifts
        for i in resort.lifts.indices {
            if resort.lifts[i].status == .running {
                let wear = Int.random(in: 1...3)
                resort.lifts[i].conditionPercent = max(20, resort.lifts[i].conditionPercent - wear)
                resort.lifts[i].passengersToday = Int(Double(resort.totalVisitorsToday) * Double.random(in: 0.6...1.4))
                
                // Lift mechanics repair active lifts
                let mechanicCount = resort.staff.filter { $0.role == .mechanic }.count
                if mechanicCount > 0 {
                    resort.lifts[i].conditionPercent = min(100, resort.lifts[i].conditionPercent + (mechanicCount * 2))
                }
            } else {
                resort.lifts[i].passengersToday = 0
            }
        }
        
        // 7. Slope Snow Compaction & Grooming Decay
        for i in resort.slopes.indices {
            if resort.slopes[i].isOpen {
                // Grooming decays as skiers ride
                let decay = Int.random(in: 8...18)
                resort.slopes[i].groomingPercent = max(10, resort.slopes[i].groomingPercent - decay)
                resort.slopes[i].skiersToday = Int(Double(resort.totalVisitorsToday) * Double.random(in: 0.4...1.2))
                
                // Add new snow from weather
                resort.slopes[i].snowDepthCm = max(30, resort.slopes[i].snowDepthCm + resort.weather.newSnowfall24hCm - 2)
            } else {
                resort.slopes[i].skiersToday = 0
            }
        }
        
        // 8. Staff Morale Dynamics
        for i in resort.staff.indices {
            let moraleDelta = netProfit > 0 ? Int.random(in: 0...2) : -Int.random(in: 1...3)
            resort.staff[i].moralePercent = max(25, min(100, resort.staff[i].moralePercent + moraleDelta))
        }
        
        // 9. Save Snapshot to Disk
        saveToDisk()
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
    
    // MARK: - Mountain Operations Actions
    
    public func toggleLiftStatus(id: UUID) {
        guard let index = resort.lifts.firstIndex(where: { $0.id == id }) else { return }
        resort.lifts[index].status = resort.lifts[index].status == .running ? .closed : .running
        saveToDisk()
    }
    
    public func toggleSlopeStatus(id: UUID) {
        guard let index = resort.slopes.firstIndex(where: { $0.id == id }) else { return }
        resort.slopes[index].isOpen.toggle()
        saveToDisk()
    }
    
    public func groomAllSlopes() {
        let groomerCount = resort.staff.filter { $0.role == .groomer }.count
        let groomCost = resort.slopes.count * 120
        guard resort.cash >= groomCost else { return }
        
        resort.cash -= groomCost
        resort.transactions.insert(
            GameTransaction(title: "Full Mountain Corduroy Grooming Fleet", amount: -groomCost, category: .slopeMaintenance, gameDay: resort.gameDay),
            at: 0
        )
        
        let efficiencyBonus = min(100, 75 + (groomerCount * 5))
        for i in resort.slopes.indices {
            resort.slopes[i].groomingPercent = efficiencyBonus
            resort.slopes[i].snowQuality = .groomedCorduroy
        }
        saveToDisk()
    }
    
    public func makeSnowOnEquippedSlopes() {
        guard resort.weather.isSnowmakingOptimal else { return }
        let snowmakingCost = resort.slopes.filter { $0.snowmakingEquipped }.count * 350
        guard resort.cash >= snowmakingCost else { return }
        
        resort.cash -= snowmakingCost
        resort.transactions.insert(
            GameTransaction(title: "Snowmaking Power & Water Reservoir Draw", amount: -snowmakingCost, category: .snowmakingEnergy, gameDay: resort.gameDay),
            at: 0
        )
        
        for i in resort.slopes.indices where resort.slopes[i].snowmakingEquipped {
            resort.slopes[i].snowDepthCm += 15
            resort.slopes[i].snowQuality = .packedPowder
        }
        resort.weather.baseDepthCm += 6
        saveToDisk()
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
        return true
    }
    
    public func hireStaff(name: String, role: StaffRole) -> Bool {
        let member = StaffMember(name: name, role: role, skillStars: Int.random(in: 3...5), moralePercent: 90)
        resort.staff.append(member)
        saveToDisk()
        return true
    }
    
    public func fireStaff(id: UUID) {
        resort.staff.removeAll(where: { $0.id == id })
        saveToDisk()
    }
    
    public func takeLoan(amount: Int) {
        resort.outstandingLoan += amount
        resort.cash += amount
        resort.transactions.insert(
            GameTransaction(title: "Alpine Commercial Bank Loan Disbursed", amount: amount, category: .capitalInvestment, gameDay: resort.gameDay),
            at: 0
        )
        saveToDisk()
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
        return true
    }
    
    public func setTicketPrices(adult: Int, child: Int) {
        resort.ticketPriceAdult = max(20, min(250, adult))
        resort.ticketPriceChild = max(10, min(150, child))
        saveToDisk()
    }
    
    public func resetToTemplate() {
        self.resort = Resort.makeParkCityTemplate()
        saveToDisk()
    }
    
    // MARK: - Disk Persistence Engine
    
    private func saveToDisk() {
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
    
    private static var documentsURL: URL? {
        FileManager.default.urls(for: .documentDirectory, in: .userDomainMask).first
    }
}
