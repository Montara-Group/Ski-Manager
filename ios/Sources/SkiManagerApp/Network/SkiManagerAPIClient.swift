import Foundation

public struct ServerHealthResponse: Codable {
    public var status: String?
    public var timestamp: String?
}

public struct LeaderboardEntry: Identifiable, Codable, Equatable {
    public var id: Int
    public var username: String
    public var resortName: String?
    public var cash: Int
    public var reputation: Int
    public var rankPosition: Int
    
    public init(id: Int, username: String, resortName: String? = nil, cash: Int, reputation: Int, rankPosition: Int) {
        self.id = id
        self.username = username
        self.resortName = resortName
        self.cash = cash
        self.reputation = reputation
        self.rankPosition = rankPosition
    }
}

public struct ApiResortResponse: Codable {
    public var status: String?
    public var resort_name: String?
    public var resort_map: String?
    public var game_day: Int?
    public var finances: ApiFinances?
    public var weather: ApiWeather?
    public var segments: [RealMapDataLoader.RawSegment]?
}

public struct ApiFinances: Codable {
    public var cash: Int?
    public var daily_visitors: Int?
    public var total_income: Int?
    public var total_expenses: Int?
}

public struct ApiWeather: Codable {
    public var game_day: Int?
    public var temperature: Int?
    public var snowfall: Int?
    public var base_depth: Int?
    public var wind_speed: Int?
    public var condition: String?
}

public actor SkiManagerAPIClient {
    public static let shared = SkiManagerAPIClient()
    
    public let baseURL = URL(string: "https://ski-manager.net")!
    private let session: URLSession
    
    public init() {
        let config = URLSessionConfiguration.default
        config.timeoutIntervalForRequest = 10.0
        config.timeoutIntervalForResource = 20.0
        self.session = URLSession(configuration: config)
    }
    
    // MARK: - Health & Maintenance Check
    
    public func checkServerHealth() async throws -> (isHealthy: Bool, isMaintenance: Bool, message: String) {
        let healthURL = baseURL.appendingPathComponent("health")
        var request = URLRequest(url: healthURL)
        request.httpMethod = "GET"
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        
        let (data, response) = try await session.data(for: request)
        guard let httpResponse = response as? HTTPURLResponse else {
            return (false, false, "Invalid server response")
        }
        
        if httpResponse.statusCode == 200 {
            return (true, false, "Server Online & Healthy (HTTP 200)")
        } else if httpResponse.statusCode == 503 {
            // Maintenance mode active
            if let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any],
               let msg = json["message"] as? String {
                return (false, true, msg)
            }
            return (false, true, "Site is undergoing Season 2 scheduled maintenance.")
        } else {
            return (false, false, "Server returned status code \(httpResponse.statusCode)")
        }
    }
    
    // MARK: - Fetch Authentic Mountain & Resort Data
    
    public func fetchResortData() async throws -> ApiResortResponse {
        let url = baseURL.appendingPathComponent("api/resort-data")
        var request = URLRequest(url: url)
        request.httpMethod = "GET"
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        
        let (data, response) = try await session.data(for: request)
        guard let httpResponse = response as? HTTPURLResponse, httpResponse.statusCode == 200 else {
            throw URLError(.badServerResponse)
        }
        
        let decoder = JSONDecoder()
        return try decoder.decode(ApiResortResponse.self, from: data)
    }
    
    // MARK: - Fetch Online Leaderboard
    
    public func fetchLeaderboard() async throws -> [LeaderboardEntry] {
        let url = baseURL.appendingPathComponent("leaderboard")
        var request = URLRequest(url: url)
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        
        do {
            let (data, response) = try await session.data(for: request)
            if let http = response as? HTTPURLResponse, http.statusCode == 200 {
                let decoder = JSONDecoder()
                if let entries = try? decoder.decode([LeaderboardEntry].self, from: data) {
                    return entries
                }
            }
        } catch {
            // Graceful fallback to cached mock pioneer leaderboard
        }
        
        return Self.fallbackPioneerLeaderboard
    }
    
    public static var fallbackPioneerLeaderboard: [LeaderboardEntry] {
        [
            LeaderboardEntry(id: 101, username: "AlpineApex", resortName: "Apex Summit Peaks", cash: 3850000, reputation: 98, rankPosition: 1),
            LeaderboardEntry(id: 102, username: "PowderHound", resortName: "Wasatch Powder Haven", cash: 3200000, reputation: 95, rankPosition: 2),
            LeaderboardEntry(id: 103, username: "GlacierKing", resortName: "Silver Creek Basin", cash: 2950000, reputation: 94, rankPosition: 3),
            LeaderboardEntry(id: 104, username: "Snowbird_Pro", resortName: "Little Cottonwood Crest", cash: 2450000, reputation: 91, rankPosition: 4),
            LeaderboardEntry(id: 105, username: "VailVenture", resortName: "Front Range Bowls", cash: 2100000, reputation: 89, rankPosition: 5)
        ]
    }
}
