import Foundation
import SwiftUI

public enum ResortDifficulty: String, Codable, CaseIterable, Identifiable {
    case casual = "Scenic Alpine (Casual)"
    case standard = "Resort Manager (Standard)"
    case expert = "Mountain Hardcore (Expert)"
    
    public var id: String { rawValue }
    
    public var startingCash: Int {
        switch self {
        case .casual: return 750000
        case .standard: return 400000
        case .expert: return 180000
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
    public var mapName: String
    public var difficulty: ResortDifficulty
    public var seasonNumber: Int
    public var gameDay: Int
    public var cash: Int
    public var genepis: Int
    public var reputationScore: Int // 0 to 100
    public var ticketPriceAdult: Int
    public var ticketPriceChild: Int
    public var totalVisitorsToday: Int
    public var outstandingLoan: Int
    
    // Domain Entities
    public var lifts: [Lift]
    public var slopes: [Slope]
    public var staff: [StaffMember]
    public var buildings: [ResortBuilding]
    public var weather: MountainWeather
    public var transactions: [GameTransaction]
    
    // Selected Sector (1: Base Village, 2: Advanced Peaks Expansion)
    public var unlockedSectors: [Int]
    
    public init(
        id: UUID = UUID(),
        name: String = "Park City Mountain Resort",
        mapName: String = "Park City, Utah",
        difficulty: ResortDifficulty = .standard,
        seasonNumber: Int = 1,
        gameDay: Int = 42,
        cash: Int = 425000,
        genepis: Int = 150,
        reputationScore: Int = 82,
        ticketPriceAdult: Int = 85,
        ticketPriceChild: Int = 45,
        totalVisitorsToday: Int = 2480,
        outstandingLoan: Int = 100000,
        lifts: [Lift] = [],
        slopes: [Slope] = [],
        staff: [StaffMember] = [],
        buildings: [ResortBuilding] = [],
        weather: MountainWeather = MountainWeather(),
        transactions: [GameTransaction] = [],
        unlockedSectors: [Int] = [1, 2]
    ) {
        self.id = id
        self.name = name
        self.mapName = mapName
        self.difficulty = difficulty
        self.seasonNumber = seasonNumber
        self.gameDay = gameDay
        self.cash = cash
        self.genepis = genepis
        self.reputationScore = reputationScore
        self.ticketPriceAdult = ticketPriceAdult
        self.ticketPriceChild = ticketPriceChild
        self.totalVisitorsToday = totalVisitorsToday
        self.outstandingLoan = outstandingLoan
        self.lifts = lifts
        self.slopes = slopes
        self.staff = staff
        self.buildings = buildings
        self.weather = weather
        self.transactions = transactions
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
        lifts.filter { $0.status == .running }.reduce(0) { $0 + $1.dailyMaintenanceCost }
    }
    
    public var dailySlopeUpkeep: Int {
        slopes.filter { $0.isOpen }.reduce(0) { $0 + $1.dailyUpkeepCost }
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
        lifts.filter { $0.status == .running }.reduce(0) { $0 + $1.hourlyCapacity }
    }
    
    public var totalSkiableLengthKm: Double {
        let totalMeters = slopes.reduce(0) { $0 + $1.lengthMeters }
        return Double(totalMeters) / 1000.0
    }
    
    public var openSlopesCount: Int {
        slopes.filter { $0.isOpen }.count
    }
    
    public var runningLiftsCount: Int {
        lifts.filter { $0.status == .running }.count
    }
    
    public var mountainSafetyScore: Int {
        let patrollerCount = staff.filter { $0.role == .skiPatrol }.count
        let totalSlopesCount = max(1, slopes.count)
        let patrollerRatio = Double(patrollerCount) / Double(totalSlopesCount)
        var score = Int(patrollerRatio * 100.0 * 2.5)
        
        // Deduct for extreme avalanche risks or blizzard conditions
        score -= (weather.avalancheDangerLevel - 1) * 8
        if weather.windSpeedKmh > 40 { score -= 12 }
        
        return max(15, min(100, score))
    }
    
    // MARK: - Template Park City Mountain
    
    public static func makeParkCityTemplate() -> Resort {
        let defaultLifts = [
            Lift(name: "First Time Express", liftType: .magicCarpet, sectorNumber: 1, lengthMeters: 280, verticalRiseMeters: 45, status: .running, conditionPercent: 98),
            Lift(name: "Payday Express 6-Pack", liftType: .sixPackExpress, sectorNumber: 1, lengthMeters: 1450, verticalRiseMeters: 390, status: .running, conditionPercent: 92),
            Lift(name: "Crescent High-Speed Quad", liftType: .expressQuad, sectorNumber: 1, lengthMeters: 1680, verticalRiseMeters: 480, status: .running, conditionPercent: 88),
            Lift(name: "Town Lift Historic Double", liftType: .fixedDouble, sectorNumber: 1, lengthMeters: 1220, verticalRiseMeters: 290, status: .running, conditionPercent: 85),
            Lift(name: "Jupiter Peak Tram", liftType: .aerialTram, sectorNumber: 2, lengthMeters: 2150, verticalRiseMeters: 620, status: .running, conditionPercent: 96),
            Lift(name: "Silverlode Express", liftType: .expressQuad, sectorNumber: 2, lengthMeters: 1540, verticalRiseMeters: 410, status: .running, conditionPercent: 90),
            Lift(name: "King Con 6-Pack", liftType: .sixPackExpress, sectorNumber: 2, lengthMeters: 1890, verticalRiseMeters: 510, status: .running, conditionPercent: 94)
        ]
        
        let defaultSlopes = [
            Slope(name: "First Time Runway", difficulty: .green, sectorNumber: 1, lengthMeters: 650, verticalDropMeters: 75, groomingPercent: 100, snowmakingEquipped: true, nightSkiingLighted: true, isOpen: true, snowDepthCm: 110, snowQuality: .groomedCorduroy),
            Slope(name: "Claimjumper Cruiser", difficulty: .blue, sectorNumber: 1, lengthMeters: 1850, verticalDropMeters: 380, groomingPercent: 90, snowmakingEquipped: true, nightSkiingLighted: true, isOpen: true, snowDepthCm: 125, snowQuality: .groomedCorduroy),
            Slope(name: "Payday Bowl", difficulty: .blue, sectorNumber: 1, lengthMeters: 1420, verticalDropMeters: 340, groomingPercent: 85, snowmakingEquipped: true, nightSkiingLighted: false, isOpen: true, snowDepthCm: 130, snowQuality: .packedPowder),
            Slope(name: "Silver King Black Diamond", difficulty: .blackDiamond, sectorNumber: 1, lengthMeters: 1200, verticalDropMeters: 420, groomingPercent: 40, snowmakingEquipped: true, nightSkiingLighted: false, isOpen: true, snowDepthCm: 140, snowQuality: .deepPowder),
            Slope(name: "Jupiter Bowl Powder Chutes", difficulty: .doubleBlack, sectorNumber: 2, lengthMeters: 1980, verticalDropMeters: 610, groomingPercent: 0, snowmakingEquipped: false, nightSkiingLighted: false, isOpen: true, snowDepthCm: 165, snowQuality: .deepPowder),
            Slope(name: "McConkey's Ridge", difficulty: .blackDiamond, sectorNumber: 2, lengthMeters: 1650, verticalDropMeters: 490, groomingPercent: 30, snowmakingEquipped: false, nightSkiingLighted: false, isOpen: true, snowDepthCm: 150, snowQuality: .deepPowder),
            Slope(name: "3 Kings Terrain Park", difficulty: .terrainPark, sectorNumber: 1, lengthMeters: 920, verticalDropMeters: 190, groomingPercent: 95, snowmakingEquipped: true, nightSkiingLighted: true, isOpen: true, snowDepthCm: 120, snowQuality: .groomedCorduroy)
        ]
        
        let defaultStaff = [
            StaffMember(name: "Hans Gruber", role: .liftOperator, salaryPerDay: 130, skillStars: 4, moralePercent: 88),
            StaffMember(name: "Sarah Lindgren", role: .skiPatrol, salaryPerDay: 200, skillStars: 5, moralePercent: 94),
            StaffMember(name: "Marcus Vance", role: .groomer, salaryPerDay: 220, skillStars: 4, moralePercent: 86),
            StaffMember(name: "Elena Rostova", role: .instructor, salaryPerDay: 170, skillStars: 4, moralePercent: 90),
            StaffMember(name: "Dave Chen", role: .mechanic, salaryPerDay: 240, skillStars: 5, moralePercent: 84),
            StaffMember(name: "Chloe Miller", role: .hospitality, salaryPerDay: 120, skillStars: 3, moralePercent: 89)
        ]
        
        let defaultBuildings = [
            ResortBuilding(name: "Grand Summit Mountain Hotel", buildingType: .grandHotel, upgradeLevel: 2, isOpen: true, sectorNumber: 1),
            ResortBuilding(name: "Payday Base Lodge & Cafeteria", buildingType: .mountainLodge, upgradeLevel: 3, isOpen: true, sectorNumber: 1),
            ResortBuilding(name: "The Corner Stone Après-Ski Bar", buildingType: .apresSkiBistro, upgradeLevel: 2, isOpen: true, sectorNumber: 1),
            ResortBuilding(name: "Park City Demo Rental Center", buildingType: .rentalShop, upgradeLevel: 2, isOpen: true, sectorNumber: 1),
            ResortBuilding(name: "Base Clinic & First Aid Post", buildingType: .medicalClinic, upgradeLevel: 1, isOpen: true, sectorNumber: 1)
        ]
        
        let initialTransactions = [
            GameTransaction(title: "Lift Ticket Sales (Game Day 41)", amount: 184500, category: .ticketSales, gameDay: 41),
            GameTransaction(title: "Lodge & Restaurant Receipts", amount: 14200, category: .buildingRevenue, gameDay: 41),
            GameTransaction(title: "Staff Payroll Deductions", amount: -1080, category: .staffWages, gameDay: 41),
            GameTransaction(title: "Lift Fleet Electricity & Maintenance", amount: -2100, category: .liftMaintenance, gameDay: 41),
            GameTransaction(title: "Snowmaking & Groomer Diesel", amount: -850, category: .snowmakingEnergy, gameDay: 41)
        ]
        
        return Resort(
            name: "Park City Mountain Base",
            mapName: "Park City, Utah",
            difficulty: .standard,
            seasonNumber: 1,
            gameDay: 42,
            cash: 485000,
            genepis: 175,
            reputationScore: 86,
            ticketPriceAdult: 89,
            ticketPriceChild: 49,
            totalVisitorsToday: 2650,
            outstandingLoan: 85000,
            lifts: defaultLifts,
            slopes: defaultSlopes,
            staff: defaultStaff,
            buildings: defaultBuildings,
            weather: MountainWeather(),
            transactions: initialTransactions,
            unlockedSectors: [1, 2]
        )
    }
}
