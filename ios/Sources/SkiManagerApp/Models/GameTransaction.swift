import Foundation
import SwiftUI

public enum TransactionCategory: String, Codable, CaseIterable {
    case ticketSales = "Lift Tickets"
    case buildingRevenue = "Lodge & Dining"
    case staffWages = "Staff Salaries"
    case liftMaintenance = "Lift Upkeep"
    case slopeMaintenance = "Slope Grooming"
    case snowmakingEnergy = "Snowmaking Power"
    case bankInterest = "Loan Interest"
    case capitalInvestment = "Capital Build"
    
    public var isIncome: Bool {
        switch self {
        case .ticketSales, .buildingRevenue:
            return true
        default:
            return false
        }
    }
    
    public var sfSymbol: String {
        switch self {
        case .ticketSales: return "ticket.fill"
        case .buildingRevenue: return "fork.knife"
        case .staffWages: return "person.badge.shield.checkmark.fill"
        case .liftMaintenance: return "wrench.and.screwdriver.fill"
        case .slopeMaintenance: return "snow"
        case .snowmakingEnergy: return "bolt.fill"
        case .bankInterest: return "building.columns.fill"
        case .capitalInvestment: return "hammer.fill"
        }
    }
    
    public var tintColor: Color {
        isIncome ? AlpineTheme.forestGreen : AlpineTheme.avalancheRed
    }
}

public struct GameTransaction: Identifiable, Codable, Equatable, Hashable {
    public var id: UUID
    public var title: String
    public var amount: Int
    public var category: TransactionCategory
    public var gameDay: Int
    public var timestamp: Date
    
    public init(
        id: UUID = UUID(),
        title: String,
        amount: Int,
        category: TransactionCategory,
        gameDay: Int,
        timestamp: Date = Date()
    ) {
        self.id = id
        self.title = title
        self.amount = amount
        self.category = category
        self.gameDay = gameDay
        self.timestamp = timestamp
    }
    
    public var formattedAmount: String {
        let sign = category.isIncome ? "+" : "-"
        return "\(sign)€\(abs(amount).formatted())"
    }
}
