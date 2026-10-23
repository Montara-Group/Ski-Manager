import SwiftUI

public struct MainTabView: View {
    @State private var selectedTab: Int = 0
    
    public init() {}
    
    public var body: some View {
        TabView(selection: $selectedTab) {
            MountainView()
                .tabItem {
                    Label(
                        "Mountain",
                        systemImage: selectedTab == 0 ? "mountain.2.fill" : "mountain.2"
                    )
                }
                .tag(0)
            
            ResortOpsView()
                .tabItem {
                    Label(
                        "Resort Ops",
                        systemImage: "gauge.with.dots.needle.bottom.50percent"
                    )
                }
                .tag(1)
            
            FinancesView()
                .tabItem {
                    Label(
                        "Finances",
                        systemImage: "chart.line.uptrend.xyaxis"
                    )
                }
                .tag(2)
            
            StaffLodgeView()
                .tabItem {
                    Label(
                        "Staff & Village",
                        systemImage: selectedTab == 3 ? "person.3.sequence.fill" : "person.3.sequence"
                    )
                }
                .tag(3)
            
            CloudSyncView()
                .tabItem {
                    Label(
                        "Sync",
                        systemImage: selectedTab == 4 ? "cloud.fill" : "cloud"
                    )
                }
                .tag(4)
        }
        .tint(AlpineTheme.glacierBlue)
        .sensoryFeedback(.selection, trigger: selectedTab)
    }
}
