import SwiftUI

@main
public struct SkiManagerApp: App {
    @State private var store = ResortStore()
    
    public init() {}
    
    public var body: some Scene {
        WindowGroup {
            MainTabView()
                .environment(store)
                .preferredColorScheme(.dark) // Deep Alpine Night aesthetic
        }
    }
}
