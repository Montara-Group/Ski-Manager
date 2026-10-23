import Foundation
import SwiftUI

public enum RealMapDataLoader {
    
    public struct MapPayload: Codable {
        public var segments: [RawSegment]
        public var sectors: [RawSector]?
    }
    
    public struct RawSegment: Codable {
        public var id: Int
        public var resort_map: String?
        public var type: String
        public var difficulty: String?
        public var name: String
        public var points: [[Double]]
        public var length_meters: Int?
        public var sector: Int?
        public var active: Bool?
    }
    
    public struct RawSector: Codable {
        public var id: Int
        public var resort_map: String?
        public var name: String
        public var color: String?
        public var boundary_points: [[Double]]?
        public var released: Bool?
        public var visible: Bool?
    }
    
    /// Loads authentic segments from bundled RealMapData.json, matching Demo.php starting state
    public static func loadBundledMapData() -> (segments: [RealMapSegment], sectors: [RealMapSector]) {
        var rawData: Data? = nil
        
        // 1. Try Bundle.main
        if let url = Bundle.main.url(forResource: "RealMapData", withExtension: "json") {
            rawData = try? Data(contentsOf: url)
        }
        
        // 2. Try adjacent resource paths
        if rawData == nil {
            let possiblePaths = [
                "RealMapData.json",
                "Resources/RealMapData.json",
                "Sources/SkiManagerApp/Resources/RealMapData.json"
            ]
            for p in possiblePaths {
                if FileManager.default.fileExists(atPath: p) {
                    rawData = try? Data(contentsOf: URL(fileURLWithPath: p))
                    if rawData != nil { break }
                }
            }
        }
        
        guard let data = rawData,
              let payload = try? JSONDecoder().decode(MapPayload.self, from: data) else {
            return (fallbackSegments(), fallbackSectors())
        }
        
        // Starting built segment IDs matching Demo.php
        let startingBuiltLiftIds: Set<Int> = [18, 21, 22, 25]
        let startingBuiltSlopeIds: Set<Int> = [84, 85, 86, 87, 88, 89, 90, 91]
        
        // Demo lift name overrides
        let liftNames: [Int: String] = [
            18: "First Time Quad",
            21: "Payday Express",
            25: "Bonanza High-Speed 6",
            22: "Town Lift Triple"
        ]
        
        // Demo slope name overrides
        let slopeNames: [Int: String] = [
            84: "First Time Cruiser",
            85: "Payday Run",
            86: "Crescent Ridge",
            87: "Lost Prospector",
            88: "Silver King Chute",
            89: "Thaynes Canyon",
            90: "3 Kings Park",
            91: "Quit'N Time"
        ]
        
        var parsedSegments: [RealMapSegment] = []
        for raw in payload.segments {
            let id = raw.id
            let isLift = raw.type.lowercased() == "lift"
            let isBuilt = isLift ? startingBuiltLiftIds.contains(id) : startingBuiltSlopeIds.contains(id)
            
            var name = raw.name
            if let customLiftName = liftNames[id] {
                name = customLiftName
            } else if let customSlopeName = slopeNames[id] {
                name = customSlopeName
            }
            
            parsedSegments.append(RealMapSegment(
                id: id,
                resortMap: raw.resort_map ?? "ParkCity",
                type: raw.type,
                difficulty: raw.difficulty,
                name: name,
                points: raw.points,
                lengthMeters: raw.length_meters ?? 800,
                sector: raw.sector ?? (isBuilt ? 1 : 2),
                active: raw.active ?? true,
                isBuilt: isBuilt,
                isOpen: true,
                capacity: isLift ? (id == 25 ? 3000 : (id == 21 ? 2400 : 1400)) : 0,
                conditionPercent: 100,
                groomingPercent: 90
            ))
        }
        
        var parsedSectors: [RealMapSector] = []
        if let rawSectors = payload.sectors {
            for s in rawSectors {
                parsedSectors.append(RealMapSector(
                    id: s.id,
                    resortMap: s.resort_map ?? "ParkCity",
                    name: s.name,
                    color: s.color ?? "#3b82f6",
                    boundaryPoints: s.boundary_points ?? [],
                    released: s.released ?? true,
                    visible: s.visible ?? true
                ))
            }
        }
        if parsedSectors.isEmpty {
            parsedSectors = fallbackSectors()
        }
        
        return (parsedSegments, parsedSectors)
    }
    
    public static func fallbackSectors() -> [RealMapSector] {
        [
            RealMapSector(
                id: 6,
                resortMap: "ParkCity",
                name: "Sector 1: Historic Base",
                color: "#3b82f6",
                boundaryPoints: [
                    [120.125, 72.125], [73.75, 73], [48.375, 96.75], [51.375, 151.375],
                    [75.875, 166.75], [89.875, 170.75], [91.375, 175.375], [107, 162.5],
                    [106.75, 158.1875], [112.1875, 154.8125], [122, 147.125], [128.8125, 139.625],
                    [132, 134.5], [137.3125, 126.0625], [140.0625, 118.5], [141.9375, 109.25],
                    [150.6875, 108], [153.9375, 105.1875], [152.5, 99.6875], [146.9375, 99],
                    [140, 86.75], [135.9375, 79.25]
                ],
                released: true,
                visible: true
            )
        ]
    }
    
    public static func fallbackSegments() -> [RealMapSegment] {
        [
            RealMapSegment(
                id: 18,
                resortMap: "ParkCity",
                type: "lift",
                difficulty: nil,
                name: "First Time Quad",
                points: [[55.875, 102.25], [133.375, 83.25]],
                lengthMeters: 1197,
                sector: 1,
                active: true,
                isBuilt: true,
                isOpen: true,
                capacity: 1400
            ),
            RealMapSegment(
                id: 21,
                resortMap: "ParkCity",
                type: "lift",
                difficulty: nil,
                name: "Payday Express",
                points: [[66.25, 155.125], [80, 157.4375], [103.875, 160], [108.75, 160.3125]],
                lengthMeters: 643,
                sector: 1,
                active: true,
                isBuilt: true,
                isOpen: true,
                capacity: 2400
            ),
            RealMapSegment(
                id: 25,
                resortMap: "ParkCity",
                type: "lift",
                difficulty: nil,
                name: "Bonanza High-Speed 6",
                points: [[70.125, 144.4375], [91.875, 130.6875], [146.75, 103.375], [150.8125, 101.875]],
                lengthMeters: 1370,
                sector: 1,
                active: true,
                isBuilt: true,
                isOpen: true,
                capacity: 3000
            ),
            RealMapSegment(
                id: 22,
                resortMap: "ParkCity",
                type: "lift",
                difficulty: nil,
                name: "Town Lift Triple",
                points: [[69.0625, 154.0625], [77.25, 150.5], [93.125, 143.125]],
                lengthMeters: 397,
                sector: 1,
                active: true,
                isBuilt: true,
                isOpen: true,
                capacity: 1200
            ),
            RealMapSegment(
                id: 84,
                resortMap: "ParkCity",
                type: "slope",
                difficulty: "green",
                name: "First Time Cruiser",
                points: [[133.375, 83.25], [118.875, 94.625], [101.5, 99.875], [79.25, 101.875], [55.875, 102.25]],
                lengthMeters: 1450,
                sector: 1,
                active: true,
                isBuilt: true,
                isOpen: true
            ),
            RealMapSegment(
                id: 85,
                resortMap: "ParkCity",
                type: "slope",
                difficulty: "blue",
                name: "Payday Run",
                points: [[108.75, 160.3125], [94.4375, 153.3125], [83.1875, 152.8125], [66.25, 155.125]],
                lengthMeters: 1820,
                sector: 1,
                active: true,
                isBuilt: true,
                isOpen: true
            ),
            RealMapSegment(
                id: 86,
                resortMap: "ParkCity",
                type: "slope",
                difficulty: "black",
                name: "Silver King Chute",
                points: [[150.8125, 101.875], [130.5, 114.25], [91.875, 130.6875], [70.125, 144.4375]],
                lengthMeters: 1680,
                sector: 1,
                active: true,
                isBuilt: true,
                isOpen: true
            )
        ]
    }
}
