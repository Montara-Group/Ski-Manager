import SwiftUI

public struct LiftRowView: View {
    public let lift: Lift
    public let onToggle: () -> Void
    
    public init(lift: Lift, onToggle: @escaping () -> Void) {
        self.lift = lift
        self.onToggle = onToggle
    }
    
    public var body: some View {
        HStack(spacing: 14) {
            // Lift Icon Tile
            ZStack {
                RoundedRectangle(cornerRadius: 14, style: .continuous)
                    .fill(lift.status == .running ? AlpineTheme.glacierBlue.opacity(0.15) : Color.secondary.opacity(0.12))
                    .frame(width: 46, height: 46)
                
                Image(systemName: lift.liftType.sfSymbol)
                    .font(.title3)
                    .foregroundStyle(lift.status == .running ? AlpineTheme.glacierBlue : .secondary)
            }
            
            // Name & Spec Details
            VStack(alignment: .leading, spacing: 4) {
                HStack(spacing: 6) {
                    Text(lift.name)
                        .font(.headline)
                        .foregroundStyle(.primary)
                        .lineLimit(1)
                    
                    if lift.sectorNumber == 2 {
                        Text("Sector 2")
                            .font(.system(size: 10, weight: .bold))
                            .foregroundStyle(AlpineTheme.peakAmber)
                            .padding(.horizontal, 6)
                            .padding(.vertical, 2)
                            .background(Capsule().fill(AlpineTheme.peakAmber.opacity(0.15)))
                    }
                }
                
                HStack(spacing: 8) {
                    Text(lift.liftType.rawValue)
                        .font(.caption)
                        .foregroundStyle(.secondary)
                    
                    Text("•")
                        .font(.caption2)
                        .foregroundStyle(.tertiary)
                    
                    Text("\(lift.lengthMeters)m (\(lift.formattedRideTime))")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                        .monospacedDigit()
                }
                
                // Condition & Capacity Bar
                HStack(spacing: 8) {
                    ProgressView(value: Double(lift.conditionPercent), total: 100.0)
                        .tint(lift.conditionPercent > 60 ? AlpineTheme.forestGreen : AlpineTheme.peakAmber)
                        .frame(width: 60)
                    
                    Text("\(lift.conditionPercent)% Health")
                        .font(.system(size: 11, weight: .medium))
                        .foregroundStyle(.secondary)
                        .monospacedDigit()
                    
                    Spacer()
                    
                    Text("\(lift.hourlyCapacity) skiers/h")
                        .font(.system(size: 11, weight: .semibold))
                        .foregroundStyle(.primary)
                        .monospacedDigit()
                }
                .padding(.top, 2)
            }
            
            // Toggle Power Button
            Button(action: onToggle) {
                Image(systemName: lift.status == .running ? "power.circle.fill" : "power.circle")
                    .font(.title2)
                    .foregroundStyle(lift.status == .running ? AlpineTheme.forestGreen : .secondary)
                    .frame(width: 44, height: 44) // Apple touch target minimum
                    .contentShape(Rectangle())
            }
            .buttonStyle(.plain)
            .sensoryFeedback(.impact(weight: .medium), trigger: lift.status)
        }
        .padding(14)
        .background {
            RoundedRectangle(cornerRadius: 18, style: .continuous)
                .fill(.ultraThinMaterial)
                .overlay(
                    RoundedRectangle(cornerRadius: 18, style: .continuous)
                        .strokeBorder(Color.white.opacity(0.1), lineWidth: 0.5)
                )
        }
    }
}
