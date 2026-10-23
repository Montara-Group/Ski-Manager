import SwiftUI

public struct QuickActionGrid: View {
    @Environment(ResortStore.self) private var store
    
    @State private var showingAdvanceAlert: Bool = false
    
    public init() {}
    
    public var body: some View {
        VStack(spacing: 12) {
            // Primary Thumb Action: Advance Day
            Button {
                store.advanceDay()
            } label: {
                HStack(spacing: 8) {
                    Image(systemName: "forward.fill")
                        .font(.headline)
                    Text("Advance Game Day \(store.resort.gameDay + 1)")
                        .font(.headline.weight(.bold))
                    Spacer()
                    Text("Day \(store.resort.gameDay)")
                        .font(.subheadline)
                        .foregroundStyle(.secondary)
                        .monospacedDigit()
                }
                .padding(.horizontal, 20)
                .frame(maxWidth: .infinity)
                .frame(height: 54)
                .background {
                    RoundedRectangle(cornerRadius: 18, style: .continuous)
                        .fill(
                            LinearGradient(
                                colors: [AlpineTheme.glacierBlue, Color(red: 0.12, green: 0.58, blue: 0.88)],
                                startPoint: .topLeading,
                                endPoint: .bottomTrailing
                            )
                        )
                        .shadow(color: AlpineTheme.glacierBlue.opacity(0.35), radius: 10, x: 0, y: 5)
                }
                .foregroundStyle(.black)
            }
            .buttonStyle(AlpineGlassButtonStyle(isProminent: true))
            .sensoryFeedback(.impact(weight: .medium), trigger: store.resort.gameDay)
            
            // Secondary Quick Actions
            HStack(spacing: 12) {
                // Corduroy Grooming Action
                Button {
                    store.groomAllSlopes()
                } label: {
                    HStack(spacing: 6) {
                        Image(systemName: "snow")
                            .foregroundStyle(AlpineTheme.glacierBlue)
                        Text("Groom Trails")
                            .font(.caption.weight(.bold))
                    }
                    .frame(maxWidth: .infinity)
                    .frame(height: 44)
                }
                .buttonStyle(AlpineGlassButtonStyle())
                
                // Snowmaking Action
                Button {
                    store.makeSnowOnEquippedSlopes()
                } label: {
                    HStack(spacing: 6) {
                        Image(systemName: "fan.fill")
                            .foregroundStyle(store.resort.weather.isSnowmakingOptimal ? .cyan : .secondary)
                        Text("Snowmaking")
                            .font(.caption.weight(.bold))
                    }
                    .frame(maxWidth: .infinity)
                    .frame(height: 44)
                }
                .buttonStyle(AlpineGlassButtonStyle())
                .disabled(!store.resort.weather.isSnowmakingOptimal)
                .opacity(store.resort.weather.isSnowmakingOptimal ? 1.0 : 0.6)
            }
        }
    }
}
