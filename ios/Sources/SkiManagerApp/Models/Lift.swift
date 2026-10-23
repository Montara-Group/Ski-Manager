import Foundation
import SwiftUI

public enum LiftType: String, Codable, CaseIterable, Identifiable {
    case magicCarpet = "Magic Carpet"
    case fixedDouble = "Fixed 2-Seater Chair"
    case expressQuad = "High-Speed Quad"
    case sixPackExpress = "6-Seater Express"
    case gondola = "8-Passenger Gondola"
    case aerialTram = "Aerial Tramway"
    
    public var id: String { rawValue }
    
    public var sfSymbol: String {
        switch self {
        case .magicCarpet:
            return "figure.snowboarding"
        case .fixedDouble, .expressQuad, .sixPackExpress:
            return "chairlift.fill"
        case .gondola:
            return "cablecar.fill"
        case .aerialTram:
            return "tram.fill"
        }
    }
    
    public var baseCapacityPerHour: Int {
        switch self {
        case .magicCarpet: return 800
        case .fixedDouble: return 1200
        case .expressQuad: return 2400
        case .sixPackExpress: return 3200
        case .gondola: return 2800
        case .aerialTram: return 1500
        }
    }
    
    public var baseConstructionCost: Int {
        switch self {
        case .magicCarpet: return 45000
        case .fixedDouble: return 120000
        case .expressQuad: return 350000
        case .sixPackExpress: return 600000
        case .gondola: return 950000
        case .aerialTram: return 1800000
        }
    }
    
    public var baseDailyMaintenance: Int {
        switch self {
        case .magicCarpet: return 60
        case .fixedDouble: return 180
        case .expressQuad: return 420
        case .sixPackExpress: return 650
        case .gondola: return 950
        case .aerialTram: return 1400
        }
    }
    
    public var speedMetersPerSecond: Double {
        switch self {
        case .magicCarpet: return 1.2
        case .fixedDouble: return 2.3
        case .expressQuad: return 5.0
        case .sixPackExpress: return 5.5
        case .gondola: return 6.0
        case .aerialTram: return 8.5
        }
    }
}

public enum LiftStatus: String, Codable, CaseIterable {
    case running = "Running"
    case closed = "Closed"
    case maintenance = "Maintenance"
    case windHold = "Wind Hold"
    
    public var sfSymbol: String {
        switch self {
        case .running: return "play.circle.fill"
        case .closed: return "stop.circle.fill"
        case .maintenance: return "wrench.and.screwdriver.fill"
        case .windHold: return "wind.circle.fill"
        }
    }
    
    public var tintColor: Color {
        switch self {
        case .running: return AlpineTheme.forestGreen
        case .closed: return .secondary
        case .maintenance: return AlpineTheme.peakAmber
        case .windHold: return AlpineTheme.avalancheRed
        }
    }
}

public struct Lift: Identifiable, Codable, Equatable, Hashable {
    public var id: UUID
    public var name: String
    public var liftType: LiftType
    public var sectorNumber: Int
    public var lengthMeters: Int
    public var verticalRiseMeters: Int
    public var status: LiftStatus
    public var conditionPercent: Int
    public var passengersToday: Int
    public var customDailyMaintenance: Int?
    
    public init(
        id: UUID = UUID(),
        name: String,
        liftType: LiftType,
        sectorNumber: Int = 1,
        lengthMeters: Int = 850,
        verticalRiseMeters: Int = 260,
        status: LiftStatus = .running,
        conditionPercent: Int = 100,
        passengersToday: Int = 0,
        customDailyMaintenance: Int? = nil
    ) {
        self.id = id
        self.name = name
        self.liftType = liftType
        self.sectorNumber = sectorNumber
        self.lengthMeters = lengthMeters
        self.verticalRiseMeters = verticalRiseMeters
        self.status = status
        self.conditionPercent = conditionPercent
        self.passengersToday = passengersToday
        self.customDailyMaintenance = customDailyMaintenance
    }
    
    public var dailyMaintenanceCost: Int {
        customDailyMaintenance ?? liftType.baseDailyMaintenance
    }
    
    public var hourlyCapacity: Int {
        liftType.baseCapacityPerHour
    }
    
    public var rideTimeMinutes: Double {
        guard liftType.speedMetersPerSecond > 0 else { return 0.0 }
        return Double(lengthMeters) / liftType.speedMetersPerSecond / 60.0
    }
    
    public var formattedRideTime: String {
        String(format: "%.1f min", rideTimeMinutes)
    }
}
