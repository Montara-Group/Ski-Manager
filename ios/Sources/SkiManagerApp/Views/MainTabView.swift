import Foundation
import SwiftUI

public struct MainTabView: View {
    @State private var selectedTab: Int = 0
    
    public init() {}
    
    public var body: some View {
        TabView(selection: $selectedTab) {
            // TAB 0: Website Dashboard
            DashboardView(selectedTab: $selectedTab)
                .tabItem {
                    Label(
                        "Dashboard",
                        systemImage: selectedTab == 0 ? "gauge.open.with.lines.needle.33percent" : "gauge.high"
                    )
                }
                .tag(0)
            
            // TAB 1: Authentic Trail Map (ParkCity.jpg + 73 Leaflet Segments)
            MountainView()
                .tabItem {
                    Label(
                        "Trail Map",
                        systemImage: selectedTab == 1 ? "map.fill" : "map"
                    )
                }
                .tag(1)
            
            // TAB 2: Commercial Village & Lodges
            VillageBuildingsView()
                .tabItem {
                    Label(
                        "Village",
                        systemImage: selectedTab == 2 ? "building.2.fill" : "building.2"
                    )
                }
                .tag(2)
            
            // TAB 3: Staff, Tickets & Bank Operations
            OperationsView()
                .tabItem {
                    Label(
                        "Operations",
                        systemImage: selectedTab == 3 ? "chart.line.uptrend.xyaxis.circle.fill" : "chart.line.uptrend.xyaxis"
                    )
                }
                .tag(3)
            
            // TAB 4: Online Network, Leaderboard & Season
            SeasonSettingsView()
                .tabItem {
                    Label(
                        "Online",
                        systemImage: selectedTab == 4 ? "globe.americas.fill" : "globe"
                    )
                }
                .tag(4)
        }
        .tint(AlpineTheme.glacierBlue)
        .sensoryFeedback(.selection, trigger: selectedTab)
    }
}
