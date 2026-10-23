import SwiftUI

public struct StatPillView: View {
    public let title: String
    public let value: String
    public let sfSymbol: String
    public var tintColor: Color = AlpineTheme.glacierBlue
    public var subtitle: String? = nil
    
    public init(
        title: String,
        value: String,
        sfSymbol: String,
        tintColor: Color = AlpineTheme.glacierBlue,
        subtitle: String? = nil
    ) {
        self.title = title
        self.value = value
        self.sfSymbol = sfSymbol
        self.tintColor = tintColor
        self.subtitle = subtitle
    }
    
    public var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            HStack(spacing: 6) {
                Image(systemName: sfSymbol)
                    .foregroundStyle(tintColor)
                    .font(.caption.weight(.bold))
                
                Text(title)
                    .font(.caption.weight(.medium))
                    .foregroundStyle(.secondary)
                    .lineLimit(1)
            }
            
            Text(value)
                .font(.title3.weight(.bold))
                .monospacedDigit()
                .foregroundStyle(.primary)
                .lineLimit(1)
            
            if let subtitle = subtitle {
                Text(subtitle)
                    .font(.caption2)
                    .foregroundStyle(.tertiary)
                    .lineLimit(1)
            }
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(14)
        .background {
            RoundedRectangle(cornerRadius: 16, style: .continuous)
                .fill(.ultraThinMaterial)
                .overlay(
                    RoundedRectangle(cornerRadius: 16, style: .continuous)
                        .strokeBorder(Color.white.opacity(0.1), lineWidth: 0.5)
                )
        }
    }
}
