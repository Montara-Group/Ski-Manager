import SwiftUI

/// Cupertino Alpine Glass visual design tokens, colors, springs, and tactile modifiers
/// Adhering to Apple Human Interface Guidelines and ProMotion 120Hz spring physics.
public enum AlpineTheme {
    // MARK: - Alpine Semantic Palette
    
    /// Vibrant glacier blue accent for primary actions, snow, and lift connections
    public static let glacierBlue = Color(red: 0.22, green: 0.74, blue: 0.97)
    
    /// High-alpine gold/amber for Season 2 highlights, currency, and VIP alerts
    public static let peakAmber = Color(red: 0.96, green: 0.62, blue: 0.04)
    
    /// Deep evergreen for green circle beginner slopes and healthy morale
    public static let forestGreen = Color(red: 0.06, green: 0.73, blue: 0.51)
    
    /// Alpine evening indigo for night skiing, menus, and deep mountain backdrops
    public static let nightIndigo = Color(red: 0.04, green: 0.07, blue: 0.16)
    
    /// Fresh powder white tint
    public static let powderWhite = Color(red: 0.97, green: 0.98, blue: 0.99)
    
    /// Expert black diamond slate
    public static let blackDiamond = Color(red: 0.06, green: 0.09, blue: 0.16)
    
    /// Emergency beacon red for blizzard warnings, avalanche risk, and patrol alerts
    public static let avalancheRed = Color(red: 0.94, green: 0.27, blue: 0.27)
    
    // MARK: - Slope Difficulty Colors
    
    public static func slopeColor(for difficulty: String) -> Color {
        switch difficulty.lowercased() {
        case "green", "beginner":
            return forestGreen
        case "blue", "intermediate":
            return glacierBlue
        case "black", "black diamond", "advanced":
            return Color.primary
        case "double black", "expert":
            return peakAmber
        case "terrain park", "park":
            return Color.purple
        default:
            return glacierBlue
        }
    }
    
    // MARK: - Fluid Spring Choreographies
    
    /// Snappy tactile spring for buttons, detents, and toggles
    public static let snappySpring = Animation.spring(response: 0.32, dampingFraction: 0.82, blendDuration: 0)
    
    /// Bouncy spring for modal appearances and celebratory badges
    public static let bouncySpring = Animation.spring(response: 0.40, dampingFraction: 0.68, blendDuration: 0)
    
    /// Gentle physics for environmental transitions (day/night, snow accumulation)
    public static let gentleSpring = Animation.spring(response: 0.55, dampingFraction: 0.88, blendDuration: 0)
}

// MARK: - Custom Button Style (Tactile Spring Press)

public struct AlpineGlassButtonStyle: ButtonStyle {
    public var isDestructive: Bool = false
    public var isProminent: Bool = false
    
    public init(isDestructive: Bool = false, isProminent: Bool = false) {
        self.isDestructive = isDestructive
        self.isProminent = isProminent
    }
    
    public func makeBody(configuration: Configuration) -> some View {
        configuration.label
            .font(.subheadline.weight(.semibold))
            .foregroundStyle(isProminent ? .black : (isDestructive ? AlpineTheme.avalancheRed : .primary))
            .padding(.horizontal, 16)
            .padding(.vertical, 12)
            .frame(minHeight: 44) // Strict 44pt Apple touch ergonomics
            .background {
                if isProminent {
                    RoundedRectangle(cornerRadius: 14, style: .continuous)
                        .fill(isDestructive ? AlpineTheme.avalancheRed : AlpineTheme.glacierBlue)
                } else {
                    RoundedRectangle(cornerRadius: 14, style: .continuous)
                        .fill(.ultraThinMaterial)
                        .overlay(
                            RoundedRectangle(cornerRadius: 14, style: .continuous)
                                .strokeBorder(Color.white.opacity(0.15), lineWidth: 0.5)
                        )
                }
            }
            .scaleEffect(configuration.isPressed ? 0.95 : 1.0)
            .opacity(configuration.isPressed ? 0.82 : 1.0)
            .animation(AlpineTheme.snappySpring, value: configuration.isPressed)
    }
}

// MARK: - View Modifiers for Frosted Glass Cards

public struct AlpineGlassCardModifier: ViewModifier {
    public var cornerRadius: CGFloat = 20
    public var padding: CGFloat = 16
    
    public func body(content: Content) -> some View {
        content
            .padding(padding)
            .background {
                RoundedRectangle(cornerRadius: cornerRadius, style: .continuous)
                    .fill(.ultraThinMaterial)
                    .overlay(
                        RoundedRectangle(cornerRadius: cornerRadius, style: .continuous)
                            .strokeBorder(Color.white.opacity(0.12), lineWidth: 0.5)
                    )
                    .shadow(color: Color.black.opacity(0.18), radius: 12, x: 0, y: 6)
            }
    }
}

public extension View {
    func alpineGlassCard(cornerRadius: CGFloat = 20, padding: CGFloat = 16) -> some View {
        self.modifier(AlpineGlassCardModifier(cornerRadius: cornerRadius, padding: padding))
    }
}
