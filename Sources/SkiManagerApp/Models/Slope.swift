import Foundation
import SwiftUI

public enum SlopeDifficulty: String, Codable, CaseIterable, Identifiable {
    case green = "Green Circle"
    case blue = "Blue Square"
    case blackDiamond = "Black Diamond"
    case doubleBlack = "Double Black Diamond"
    case terrainPark = "Terrain Park"
    
    public var id: String { rawValue }
    
    public var shortLabel: String {
        switch self {
        case .green: return "Green"
        case .blue: return "Blue"
        case .blackDiamond: return "Black"
        case .doubleBlack: return "Double Black"
        case .terrainPark: return "Park"
        }
    }
    
    public var sfSymbol: String {
        switch self {
        case .green: return "circle.fill"
        case .blue: return "square.fill"
        case .blackDiamond: return "suit.diamond.fill"
        case .doubleBlack: return "suit.diamond.fill"
        case .terrainPark: return "sparkles"
        }
    }
    
    public var color: Color {
        AlpineTheme.slopeColor(for: rawValue)
    }
    
    public var baseCutCost: Int {
        switch self {
        case .green: return 25000
        case .blue: return 45000
        case .blackDiamond: return 75000
        case .doubleBlack: return 120000
        case .terrainPark: return 90000
        }
    }
}

public enum SnowQuality: String, Codable, CaseIterable {
    case deepPowder = "Deep Powder"
    case packedPowder = "Packed Powder"
    case hardpack = "Hardpack"
    case groomedCorduroy = "Groomed Corduroy"
    case springSlush = "Spring Slush"
    case icy = "Icy Patches"
    
    public var sfSymbol: String {
        switch self {
        case .deepPowder: return "snowflake"
        case .packedPowder: return "cloud.snow.fill"
        case .groomedCorduroy: return "line.3.horizontal"
        case .hardpack: return "circle.dashed"
        case .springSlush: return "drop.fill"
        case .icy: return "exclamationmark.triangle.fill"
        }
    }
    
    public var ratingStars: Int {
        switch self {
        case .deepPowder: return 5
        case .groomedCorduroy: return 5
        case .packedPowder: return 4
        case .hardpack: return 3
        case .springSlush: return 2
        case .icy: return 1
        }
    }
}

public struct Slope: Identifiable, Codable, Equatable, Hashable {
    public var id: UUID
    public var name: String
    public var difficulty: SlopeDifficulty
    public var sectorNumber: Int
    public var lengthMeters: Int
    public var verticalDropMeters: Int
    public var groomingPercent: Int
    public var snowmakingEquipped: Bool
    public var nightSkiingLighted: Bool
    public var isOpen: Bool
    public var snowDepthCm: Int
    public var snowQuality: SnowQuality
    public var skiersToday: Int
    
    public init(
        id: UUID = UUID(),
        name: String,
        difficulty: SlopeDifficulty,
        sectorNumber: Int = 1,
        lengthMeters: Int = 1200,
        verticalDropMeters: Int = 340,
        groomingPercent: Int = 85,
        snowmakingEquipped: Bool = true,
        nightSkiingLighted: Bool = false,
        isOpen: Bool = true,
        snowDepthCm: Int = 95,
        snowQuality: SnowQuality = .groomedCorduroy,
        skiersToday: Int = 0
    ) {
        self.id = id
        self.name = name
        self.difficulty = difficulty
        self.sectorNumber = sectorNumber
        self.lengthMeters = lengthMeters
        self.verticalDropMeters = verticalDropMeters
        self.groomingPercent = groomingPercent
        self.snowmakingEquipped = snowmakingEquipped
        self.nightSkiingLighted = nightSkiingLighted
        self.isOpen = isOpen
        self.snowDepthCm = snowDepthCm
        self.snowQuality = snowQuality
        self.skiersToday = skiersToday
    }
    
    public var steepnessPercent: Double {
        guard lengthMeters > 0 else { return 0.0 }
        return (Double(verticalDropMeters) / Double(lengthMeters)) * 100.0
    }
    
    public var formattedSteepness: String {
        String(format: "%.1f%% grade", steepnessPercent)
    }
    
    public var dailyUpkeepCost: Int {
        var cost = 50
        if groomingPercent > 50 { cost += 80 }
        if nightSkiingLighted { cost += 40 }
        if snowmakingEquipped { cost += 30 }
        return cost
    }
}
