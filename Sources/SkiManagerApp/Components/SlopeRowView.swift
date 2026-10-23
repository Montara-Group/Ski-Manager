import SwiftUI

public struct SlopeRowView: View {
    public let slope: Slope
    public let onToggle: () -> Void
    
    public init(slope: Slope, onToggle: @escaping () -> Void) {
        self.slope = slope
        self.onToggle = onToggle
    }
    
    public var body: some View {
        HStack(spacing: 14) {
            // Difficulty Symbol Badge
            ZStack {
                RoundedRectangle(cornerRadius: 14, style: .continuous)
                    .fill(slope.difficulty.color.opacity(0.18))
                    .frame(width: 46, height: 46)
                
                Image(systemName: slope.difficulty.sfSymbol)
                    .font(.title3.weight(.bold))
                    .foregroundStyle(slope.difficulty.color)
            }
            
            // Name & Trail Telemetry
            VStack(alignment: .leading, spacing: 4) {
                HStack(spacing: 6) {
                    Text(slope.name)
                        .font(.headline)
                        .foregroundStyle(.primary)
                        .lineLimit(1)
                    
                    if slope.sectorNumber == 2 {
                        Text("Sector 2")
                            .font(.system(size: 10, weight: .bold))
                            .foregroundStyle(AlpineTheme.peakAmber)
                            .padding(.horizontal, 6)
                            .padding(.vertical, 2)
                            .background(Capsule().fill(AlpineTheme.peakAmber.opacity(0.15)))
                    }
                }
                
                HStack(spacing: 8) {
                    Text(slope.difficulty.shortLabel)
                        .font(.caption.weight(.medium))
                        .foregroundStyle(slope.difficulty.color)
                    
                    Text("•")
                        .font(.caption2)
                        .foregroundStyle(.tertiary)
                    
                    Text("\(slope.lengthMeters)m • \(slope.formattedSteepness)")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                        .monospacedDigit()
                }
                
                // Grooming & Surface Condition
                HStack(spacing: 8) {
                    HStack(spacing: 4) {
                        Image(systemName: slope.snowQuality.sfSymbol)
                            .font(.caption2)
                            .foregroundStyle(.secondary)
                        Text(slope.snowQuality.rawValue)
                            .font(.system(size: 11))
                            .foregroundStyle(.secondary)
                    }
                    
                    Spacer()
                    
                    HStack(spacing: 4) {
                        Image(systemName: "ruler")
                            .font(.caption2)
                            .foregroundStyle(.cyan)
                        Text("\(slope.snowDepthCm) cm")
                            .font(.system(size: 11, weight: .semibold))
                            .monospacedDigit()
                    }
                }
                .padding(.top, 2)
            }
            
            // Open / Closed Status Toggle Button
            Button(action: onToggle) {
                ZStack {
                    Capsule()
                        .fill(slope.isOpen ? AlpineTheme.forestGreen.opacity(0.15) : Color.secondary.opacity(0.12))
                        .frame(width: 60, height: 32)
                    
                    Text(slope.isOpen ? "OPEN" : "CLOSED")
                        .font(.system(size: 11, weight: .bold))
                        .foregroundStyle(slope.isOpen ? AlpineTheme.forestGreen : .secondary)
                }
                .frame(width: 44, height: 44) // Apple touch minimum
                .contentShape(Rectangle())
            }
            .buttonStyle(.plain)
            .sensoryFeedback(.impact(weight: .light), trigger: slope.isOpen)
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
