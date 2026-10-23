import Foundation
import SwiftUI

/// Geographic Coordinate on Leaflet Simple CRS Map (0..340 Lat, 0..600 Lng)
public struct RealMapPoint: Codable, Equatable, Hashable {
    public var lat: Double
    public var lng: Double
    
    public init(lat: Double, lng: Double) {
        self.lat = lat
        self.lng = lng
    }
}

/// Authentic Mountain Map Segment from ski-manager.net database (`map_segments` table)
public struct RealMapSegment: Identifiable, Codable, Equatable {
    public var id: Int
    public var resortMap: String
    public var type: String // "lift" or "slope"
    public var difficulty: String? // "green", "blue", "black", "double_black", "park"
    public var name: String
    public var points: [[Double]] // [[lat, lng]]
    public var lengthMeters: Int
    public var sector: Int
    public var active: Bool
    
    // In-game operational state
    public var isBuilt: Bool
    public var isOpen: Bool
    public var capacity: Int
    public var conditionPercent: Int
    public var groomingPercent: Int
    
    public init(
        id: Int,
        resortMap: String = "ParkCity",
        type: String,
        difficulty: String? = nil,
        name: String,
        points: [[Double]],
        lengthMeters: Int,
        sector: Int = 1,
        active: Bool = true,
        isBuilt: Bool = false,
        isOpen: Bool = true,
        capacity: Int = 1800,
        conditionPercent: Int = 100,
        groomingPercent: Int = 90
    ) {
        self.id = id
        self.resortMap = resortMap
        self.type = type
        self.difficulty = difficulty
        self.name = name
        self.points = points
        self.lengthMeters = lengthMeters
        self.sector = sector
        self.active = active
        self.isBuilt = isBuilt
        self.isOpen = isOpen
        self.capacity = capacity
        self.conditionPercent = conditionPercent
        self.groomingPercent = groomingPercent
    }
    
    public var isLift: Bool {
        type.lowercased() == "lift"
    }
    
    public var isSlope: Bool {
        type.lowercased() == "slope"
    }
    
    /// Estimated construction cost based on website algorithm (`COST_PER_METER`)
    public var constructionCost: Int {
        if isLift {
            // Standard chairlift: ~€150 per meter + base
            return max(35000, lengthMeters * 150)
        } else {
            // Slope clearing cost based on difficulty
            let costPerM: Int
            switch difficulty?.lowercased() {
            case "green": costPerM = 45
            case "blue": costPerM = 65
            case "black": costPerM = 95
            case "double_black": costPerM = 140
            case "park", "terrain_park": costPerM = 110
            default: costPerM = 60
            }
            return max(15000, lengthMeters * costPerM)
        }
    }
    
    /// Display difficulty label
    public var difficultyDisplay: String {
        if isLift { return "Lift" }
        switch difficulty?.lowercased() {
        case "green": return "Beginner (Green)"
        case "blue": return "Intermediate (Blue)"
        case "black": return "Advanced (Black Diamond)"
        case "double_black": return "Expert (Double Black)"
        case "park", "terrain_park": return "Terrain Park"
        default: return "Trail"
        }
    }
    
    /// Accurate Trail Map Polyline Color matching ski-manager.net Leaflet CSS
    public var strokeColor: Color {
        if isLift {
            return Color(red: 0.96, green: 0.62, blue: 0.04) // #f59e0b peak amber/orange
        }
        switch difficulty?.lowercased() {
        case "green":
            return Color(red: 0.13, green: 0.77, blue: 0.37) // #22c55e green
        case "blue":
            return Color(red: 0.23, green: 0.51, blue: 0.96) // #3b82f6 blue
        case "black":
            return Color(red: 0.12, green: 0.12, blue: 0.14) // #1e1e24 dark black diamond
        case "double_black":
            return Color(red: 0.86, green: 0.15, blue: 0.15) // #dc2626 crimson
        case "park", "terrain_park":
            return Color(red: 0.66, green: 0.33, blue: 0.97) // #a855f7 purple
        default:
            return Color(red: 0.58, green: 0.64, blue: 0.72) // #94a3b8 slate
        }
    }
    
    public var sfSymbol: String {
        if isLift { return "chairlift.fill" }
        switch difficulty?.lowercased() {
        case "green": return "figure.skiing.downhill"
        case "blue": return "figure.snowboarding"
        case "black": return "exclamationmark.triangle.fill"
        case "double_black": return "flame.fill"
        case "park", "terrain_park": return "sparkles"
        default: return "figure.skiing.downhill"
        }
    }
}

/// Authentic Mountain Sector boundary polygon matching `resort_sectors` table
public struct RealMapSector: Identifiable, Codable, Equatable {
    public var id: Int
    public var resortMap: String
    public var name: String
    public var color: String
    public var boundaryPoints: [[Double]] // [[lat, lng]]
    public var released: Bool
    public var visible: Bool
    
    public init(
        id: Int,
        resortMap: String = "ParkCity",
        name: String,
        color: String = "#3b82f6",
        boundaryPoints: [[Double]],
        released: Bool = true,
        visible: Bool = true
    ) {
        self.id = id
        self.resortMap = resortMap
        self.name = name
        self.color = color
        self.boundaryPoints = boundaryPoints
        self.released = released
        self.visible = visible
    }
}
