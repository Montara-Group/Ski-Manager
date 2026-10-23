import Foundation
import SwiftUI

public enum BuildingType: String, Codable, CaseIterable, Identifiable {
    case grandHotel = "Grand Alpine Hotel"
    case mountainLodge = "Summit Day Lodge"
    case apresSkiBistro = "Après-Ski Bistro & Bar"
    case rentalShop = "Demo Ski & Board Rentals"
    case proShop = "Mountain Outfitter Pro Shop"
    case medicalClinic = "Ski Patrol Medical Clinic"
    case parkingDeck = "Multi-Level Heated Parking"
    
    public var id: String { rawValue }
    
    public var sfSymbol: String {
        switch self {
        case .grandHotel: return "building.2.fill"
        case .mountainLodge: return "house.lodge.fill"
        case .apresSkiBistro: return "wineglass.fill"
        case .rentalShop: return "tag.fill"
        case .proShop: return "bag.fill"
        case .medicalClinic: return "cross.fill"
        case .parkingDeck: return "car.circle.fill"
        }
    }
    
    public var baseBuildCost: Int {
        switch self {
        case .grandHotel: return 480000
        case .mountainLodge: return 220000
        case .apresSkiBistro: return 140000
        case .rentalShop: return 95000
        case .proShop: return 85000
        case .medicalClinic: return 120000
        case .parkingDeck: return 160000
        }
    }
    
    public var baseDailyRevenue: Int {
        switch self {
        case .grandHotel: return 6800
        case .mountainLodge: return 3200
        case .apresSkiBistro: return 2400
        case .rentalShop: return 2100
        case .proShop: return 1400
        case .medicalClinic: return 0 // Public service
        case .parkingDeck: return 1800
        }
    }
    
    public var baseDailyUpkeep: Int {
        switch self {
        case .grandHotel: return 1200
        case .mountainLodge: return 480
        case .apresSkiBistro: return 380
        case .rentalShop: return 260
        case .proShop: return 190
        case .medicalClinic: return 300
        case .parkingDeck: return 220
        }
    }
}

public struct ResortBuilding: Identifiable, Codable, Equatable, Hashable {
    public var id: UUID
    public var name: String
    public var buildingType: BuildingType
    public var upgradeLevel: Int // 1 to 5
    public var isOpen: Bool
    public var sectorNumber: Int
    
    public init(
        id: UUID = UUID(),
        name: String,
        buildingType: BuildingType,
        upgradeLevel: Int = 1,
        isOpen: Bool = true,
        sectorNumber: Int = 1
    ) {
        self.id = id
        self.name = name
        self.buildingType = buildingType
        self.upgradeLevel = upgradeLevel
        self.isOpen = isOpen
        self.sectorNumber = sectorNumber
    }
    
    public var dailyRevenue: Int {
        guard isOpen else { return 0 }
        let multiplier = 1.0 + (Double(upgradeLevel - 1) * 0.45)
        return Int(Double(buildingType.baseDailyRevenue) * multiplier)
    }
    
    public var dailyUpkeep: Int {
        let multiplier = 1.0 + (Double(upgradeLevel - 1) * 0.25)
        return Int(Double(buildingType.baseDailyUpkeep) * multiplier)
    }
    
    public var nextUpgradeCost: Int {
        Int(Double(buildingType.baseBuildCost) * 0.55 * Double(upgradeLevel))
    }
}
