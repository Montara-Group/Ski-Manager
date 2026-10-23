import Foundation
import SwiftUI

public enum ResortDifficulty: String, Codable, CaseIterable, Identifiable {
    case casual = "Scenic Alpine (Casual)"
    case standard = "Resort Manager (Standard)"
    case expert = "Mountain Hardcore (Expert)"
    
    public var id: String { rawValue }
    
    public var startingCash: Int {
        switch self {
        case .casual: return 400000
        case .standard: return 250000
        case .expert: return 120000
        }
    }
    
    public var visitorMultiplier: Double {
        switch self {
        case .casual: return 1.35
        case .standard: return 1.0
        case .expert: return 0.75
        }
    }
}

public struct Resort: Identifiable, Codable, Equatable {
    public var id: UUID
    public var name: String
    public var directorUsername: String
    public var mapName: String
    public var difficulty: ResortDifficulty
    public var seasonNumber: Int
    public var gameDay: Int
    public var totalSeasonDays: Int
    public var winterSeasonDays: Int
    public var cash: Int
    public var genepis: Int
    public var reputationScore: Int // 0 to 100
    public var ticketPriceAdult: Int
    public var ticketPriceChild: Int
    public var totalVisitorsToday: Int
    public var outstandingLoan: Int
    
    // Daily Bonus & Streak
    public var dailyBonusStreak: Int
    public var lastBonusClaimDay: Int
    
    // Equipment Condition
    public var groomerConditionPct: Int
    public var snowmakingGunsCount: Int
    
    // Domain Entities
    public var lifts: [Lift]
    public var slopes: [Slope]
    public var staff: [StaffMember]
    public var buildings: [ResortBuilding]
    public var weather: MountainWeather
    public var transactions: [GameTransaction]
    
    // Authentic Website Leaflet Database Segments & Sectors
    public var realSegments: [RealMapSegment]
    public var realSectors: [RealMapSector]
    
    // Unlocked Sectors (1: Base Village, 2: Advanced Peaks)
    public var unlockedSectors: [Int]
    
    public init(
        id: UUID = UUID(),
        name: String = "Park City Mountain Resort",
        directorUsername: String = "Director Alex",
        mapName: String = "Park City",
        difficulty: ResortDifficulty = .standard,
        seasonNumber: Int = 1,
        gameDay: Int = 42,
        totalSeasonDays: Int = 135,
        winterSeasonDays: Int = 100,
        cash: Int = 250000,
        genepis: Int = 10,
        reputationScore: Int = 85,
        ticketPriceAdult: Int = 68,
        ticketPriceChild: Int = 38,
        totalVisitorsToday: Int = 480,
        outstandingLoan: Int = 0,
        dailyBonusStreak: Int = 1,
        lastBonusClaimDay: Int = 0,
        groomerConditionPct: Int = 95,
        snowmakingGunsCount: Int = 12,
        lifts: [Lift] = [],
        slopes: [Slope] = [],
        staff: [StaffMember] = [],
        buildings: [ResortBuilding] = [],
        weather: MountainWeather = MountainWeather(),
        transactions: [GameTransaction] = [],
        realSegments: [RealMapSegment] = [],
        realSectors: [RealMapSector] = [],
        unlockedSectors: [Int] = [1]
    ) {
        self.id = id
        self.name = name
        self.directorUsername = directorUsername
        self.mapName = mapName
        self.difficulty = difficulty
        self.seasonNumber = seasonNumber
        self.gameDay = gameDay
        self.totalSeasonDays = totalSeasonDays
        self.winterSeasonDays = winterSeasonDays
        self.cash = cash
        self.genepis = genepis
        self.reputationScore = reputationScore
        self.ticketPriceAdult = ticketPriceAdult
        self.ticketPriceChild = ticketPriceChild
        self.totalVisitorsToday = totalVisitorsToday
        self.outstandingLoan = outstandingLoan
        self.dailyBonusStreak = dailyBonusStreak
        self.lastBonusClaimDay = lastBonusClaimDay
        self.groomerConditionPct = groomerConditionPct
        self.snowmakingGunsCount = snowmakingGunsCount
        self.lifts = lifts
        self.slopes = slopes
        self.staff = staff
        self.buildings = buildings
        self.weather = weather
        self.transactions = transactions
        self.realSegments = realSegments
        self.realSectors = realSectors
        self.unlockedSectors = unlockedSectors
    }
    
    // MARK: - Telemetry & Financial Calculations
    
    public var dailyTicketRevenue: Int {
        let adultVisitors = Int(Double(totalVisitorsToday) * 0.72)
        let childVisitors = max(0, totalVisitorsToday - adultVisitors)
        return (adultVisitors * ticketPriceAdult) + (childVisitors * ticketPriceChild)
    }
    
    public var dailyLodgeRevenue: Int {
        buildings.reduce(0) { $0 + $1.dailyRevenue }
    }
    
    public var totalEstimatedDailyRevenue: Int {
        dailyTicketRevenue + dailyLodgeRevenue
    }
    
    public var dailyStaffExpenses: Int {
        staff.reduce(0) { $0 + $1.salaryPerDay }
    }
    
    public var dailyLiftMaintenance: Int {
        let realLiftCost = realSegments.filter { $0.isLift && $0.isBuilt && $0.isOpen }.count * 180
        let customLiftCost = lifts.filter { $0.status == .running }.reduce(0) { $0 + $1.dailyMaintenanceCost }
        return max(realLiftCost, customLiftCost)
    }
    
    public var dailySlopeUpkeep: Int {
        let realSlopeCost = realSegments.filter { $0.isSlope && $0.isBuilt && $0.isOpen }.count * 85
        let customSlopeCost = slopes.filter { $0.isOpen }.reduce(0) { $0 + $1.dailyUpkeepCost }
        return max(realSlopeCost, customSlopeCost)
    }
    
    public var dailyBuildingUpkeep: Int {
        buildings.reduce(0) { $0 + $1.dailyUpkeep }
    }
    
    public var dailyLoanInterest: Int {
        Int(Double(outstandingLoan) * 0.003) // ~0.3% daily interest (~10% annual)
    }
    
    public var totalEstimatedDailyExpenses: Int {
        dailyStaffExpenses + dailyLiftMaintenance + dailySlopeUpkeep + dailyBuildingUpkeep + dailyLoanInterest
    }
    
    public var netDailyProfit: Int {
        totalEstimatedDailyRevenue - totalEstimatedDailyExpenses
    }
    
    public var totalMountainCapacityPerHour: Int {
        let realLiftsCap = realSegments.filter { $0.isLift && $0.isBuilt && $0.isOpen }.reduce(0) { $0 + $1.capacity }
        let customLiftsCap = lifts.filter { $0.status == .running }.reduce(0) { $0 + $1.hourlyCapacity }
        return max(realLiftsCap, customLiftsCap)
    }
    
    public var totalSkiableLengthKm: Double {
        let builtRealMeters = realSegments.filter { $0.isSlope && $0.isBuilt }.reduce(0) { $0 + $1.lengthMeters }
        let customMeters = slopes.reduce(0) { $0 + $1.lengthMeters }
        return Double(max(builtRealMeters, customMeters)) / 1000.0
    }
    
    public var openSlopesCount: Int {
        let realOpen = realSegments.filter { $0.isSlope && $0.isBuilt && $0.isOpen }.count
        let customOpen = slopes.filter { $0.isOpen }.count
        return max(realOpen, customOpen)
    }
    
    public var runningLiftsCount: Int {
        let realRunning = realSegments.filter { $0.isLift && $0.isBuilt && $0.isOpen }.count
        let customRunning = lifts.filter { $0.status == .running }.count
        return max(realRunning, customRunning)
    }
    
    public var mountainSafetyScore: Int {
        let patrollerCount = staff.filter { $0.role == .skiPatrol }.count
        let totalSlopesCount = max(1, openSlopesCount)
        let patrollerRatio = Double(patrollerCount) / Double(totalSlopesCount)
        var score = Int(patrollerRatio * 100.0 * 2.5)
        
        score -= (weather.avalancheDangerLevel - 1) * 8
        if weather.windSpeedKmh > 40 { score -= 12 }
        
        return max(20, min(100, score))
    }
    
    public var canClaimDailyBonus: Bool {
        lastBonusClaimDay < gameDay
    }
    
    public var seasonProgressRatio: Double {
        Double(min(totalSeasonDays, gameDay)) / Double(totalSeasonDays)
    }
    
    // MARK: - Template Park City Mountain matching Demo.php
    
    public static func makeParkCityTemplate() -> Resort {
        let (bundledSegments, bundledSectors) = RealMapDataLoader.loadBundledMapData()
        
        // Starting lifts matching Demo.php
        let defaultLifts = [
            Lift(name: "First Time Quad", liftType: .fixedQuad, sectorNumber: 1, lengthMeters: 650, verticalRiseMeters: 140, status: .running, conditionPercent: 100),
            Lift(name: "Payday Express", liftType: .expressQuad, sectorNumber: 1, lengthMeters: 1400, verticalRiseMeters: 380, status: .running, conditionPercent: 100),
            Lift(name: "Bonanza High-Speed 6", liftType: .sixPackExpress, sectorNumber: 1, lengthMeters: 1850, verticalRiseMeters: 490, status: .running, conditionPercent: 100),
            Lift(name: "Town Lift Triple", liftType: .fixedDouble, sectorNumber: 1, lengthMeters: 1100, verticalRiseMeters: 290, status: .running, conditionPercent: 100)
        ]
        
        // Starting slopes matching Demo.php
        let defaultSlopes = [
            Slope(name: "First Time Cruiser", difficulty: .green, sectorNumber: 1, lengthMeters: 650, verticalDropMeters: 80, groomingPercent: 100, snowmakingEquipped: true, nightSkiingLighted: true, isOpen: true, snowDepthCm: 142, snowQuality: .groomedCorduroy),
            Slope(name: "Payday Run", difficulty: .blue, sectorNumber: 1, lengthMeters: 1400, verticalDropMeters: 380, groomingPercent: 90, snowmakingEquipped: true, nightSkiingLighted: true, isOpen: true, snowDepthCm: 142, snowQuality: .groomedCorduroy),
            Slope(name: "Crescent Ridge", difficulty: .blue, sectorNumber: 1, lengthMeters: 1200, verticalDropMeters: 320, groomingPercent: 85, snowmakingEquipped: true, nightSkiingLighted: false, isOpen: true, snowDepthCm: 142, snowQuality: .packedPowder),
            Slope(name: "Lost Prospector", difficulty: .green, sectorNumber: 1, lengthMeters: 900, verticalDropMeters: 150, groomingPercent: 90, snowmakingEquipped: true, nightSkiingLighted: false, isOpen: true, snowDepthCm: 142, snowQuality: .groomedCorduroy),
            Slope(name: "Silver King Chute", difficulty: .blackDiamond, sectorNumber: 1, lengthMeters: 1100, verticalDropMeters: 410, groomingPercent: 40, snowmakingEquipped: false, nightSkiingLighted: false, isOpen: true, snowDepthCm: 142, snowQuality: .deepPowder),
            Slope(name: "Thaynes Canyon", difficulty: .blackDiamond, sectorNumber: 1, lengthMeters: 1300, verticalDropMeters: 460, groomingPercent: 30, snowmakingEquipped: false, nightSkiingLighted: false, isOpen: true, snowDepthCm: 142, snowQuality: .deepPowder),
            Slope(name: "3 Kings Park", difficulty: .terrainPark, sectorNumber: 1, lengthMeters: 850, verticalDropMeters: 180, groomingPercent: 95, snowmakingEquipped: true, nightSkiingLighted: true, isOpen: true, snowDepthCm: 142, snowQuality: .groomedCorduroy),
            Slope(name: "Quit'N Time", difficulty: .blue, sectorNumber: 1, lengthMeters: 750, verticalDropMeters: 190, groomingPercent: 80, snowmakingEquipped: true, nightSkiingLighted: false, isOpen: true, snowDepthCm: 142, snowQuality: .packedPowder)
        ]
        
        // Staff matching Demo.php
        let defaultStaff = [
            StaffMember(name: "Sarah Lindgren", role: .skiPatrol, salaryPerDay: 150, skillStars: 4, moralePercent: 100),
            StaffMember(name: "Marc Dubois", role: .groomer, salaryPerDay: 110, skillStars: 4, moralePercent: 100),
            StaffMember(name: "Otto Huber", role: .mechanic, salaryPerDay: 130, skillStars: 5, moralePercent: 100),
            StaffMember(name: "Elena Rossi", role: .instructor, salaryPerDay: 95, skillStars: 3, moralePercent: 100)
        ]
        
        // Buildings matching Demo.php
        let defaultBuildings = [
            ResortBuilding(name: "Legacy Snow Lodge", buildingType: .mountainLodge, upgradeLevel: 1, isOpen: true, sectorNumber: 1),
            ResortBuilding(name: "Summit Sports Rental", buildingType: .rentalShop, upgradeLevel: 1, isOpen: true, sectorNumber: 1),
            ResortBuilding(name: "Base Camp Lodge", buildingType: .apresSkiBistro, upgradeLevel: 1, isOpen: true, sectorNumber: 1)
        ]
        
        let initialTransactions = [
            GameTransaction(title: "Lift Ticket Sales (Game Day 41)", amount: 32640, category: .ticketSales, gameDay: 41),
            GameTransaction(title: "Legacy Lodge & Rental Receipts", amount: 4800, category: .buildingRevenue, gameDay: 41),
            GameTransaction(title: "Staff Payroll Deductions", amount: -485, category: .staffWages, gameDay: 41),
            GameTransaction(title: "Lift Fleet Electricity & Maintenance", amount: -720, category: .liftMaintenance, gameDay: 41),
            GameTransaction(title: "PistenBully Groomer Operations", amount: -340, category: .slopeMaintenance, gameDay: 41)
        ]
        
        return Resort(
            name: "Park City Mountain Resort",
            directorUsername: "Director Alex",
            mapName: "Park City",
            difficulty: .standard,
            seasonNumber: 1,
            gameDay: 42,
            totalSeasonDays: 135,
            winterSeasonDays: 100,
            cash: 250000,
            genepis: 10,
            reputationScore: 85,
            ticketPriceAdult: 68,
            ticketPriceChild: 38,
            totalVisitorsToday: 480,
            outstandingLoan: 0,
            dailyBonusStreak: 1,
            lastBonusClaimDay: 0,
            groomerConditionPct: 95,
            snowmakingGunsCount: 12,
            lifts: defaultLifts,
            slopes: defaultSlopes,
            staff: defaultStaff,
            buildings: defaultBuildings,
            weather: MountainWeather(
                condition: .bluebirdPowder,
                temperatureCelsius: -4.0,
                newSnowfall24hCm: 15,
                baseDepthCm: 142,
                windSpeedKmh: 14,
                visibilityKm: 15.0,
                avalancheDangerLevel: 2,
                isSnowmakingOptimal: true,
                forecast7Day: MountainWeather.generateDefaultForecast()
            ),
            transactions: initialTransactions,
            realSegments: bundledSegments,
            realSectors: bundledSectors,
            unlockedSectors: [1]
        )
    }
}
