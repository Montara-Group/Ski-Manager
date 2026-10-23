import Foundation
import SwiftUI

public enum StaffRole: String, Codable, CaseIterable, Identifiable {
    case liftOperator = "Lift Operator"
    case skiPatrol = "Ski Patrol"
    case groomer = "Snowcat Driver"
    case instructor = "Ski Instructor"
    case mechanic = "Lift Mechanic"
    case hospitality = "Lodge Host"
    
    public var id: String { rawValue }
    
    public var sfSymbol: String {
        switch self {
        case .liftOperator: return "person.badge.shield.checkmark.fill"
        case .skiPatrol: return "cross.case.fill"
        case .groomer: return "steeringwheel"
        case .instructor: return "figure.skiing.downhill"
        case .mechanic: return "wrench.adjustable.fill"
        case .hospitality: return "cup.and.saucer.fill"
        }
    }
    
    public var baseSalary: Int {
        switch self {
        case .liftOperator: return 120
        case .skiPatrol: return 190
        case .groomer: return 210
        case .instructor: return 160
        case .mechanic: return 230
        case .hospitality: return 110
        }
    }
    
    public var roleDescription: String {
        switch self {
        case .liftOperator: return "Ensures safe boarding and disembarking on mountain chairlifts."
        case .skiPatrol: return "Provides emergency avalanche control, sweep runs, and first aid rescue."
        case .groomer: return "Operates snowcats to produce flawless corduroy ski surface."
        case .instructor: return "Teaches beginner lessons, improving visitor satisfaction."
        case .mechanic: return "Performs proactive maintenance, preventing lift breakdowns."
        case .hospitality: return "Runs resort dining, ticketing counters, and lodge hospitality."
        }
    }
}

public struct StaffMember: Identifiable, Codable, Equatable, Hashable {
    public var id: UUID
    public var name: String
    public var role: StaffRole
    public var salaryPerDay: Int
    public var skillStars: Int // 1 to 5
    public var moralePercent: Int // 0 to 100
    public var isAssigned: Bool
    
    public init(
        id: UUID = UUID(),
        name: String,
        role: StaffRole,
        salaryPerDay: Int? = nil,
        skillStars: Int = 3,
        moralePercent: Int = 85,
        isAssigned: Bool = true
    ) {
        self.id = id
        self.name = name
        self.role = role
        self.salaryPerDay = salaryPerDay ?? role.baseSalary
        self.skillStars = skillStars
        self.moralePercent = moralePercent
        self.isAssigned = isAssigned
    }
    
    public var moraleColor: Color {
        if moralePercent >= 75 {
            return AlpineTheme.forestGreen
        } else if moralePercent >= 40 {
            return AlpineTheme.peakAmber
        } else {
            return AlpineTheme.avalancheRed
        }
    }
    
    public var moraleStatus: String {
        if moralePercent >= 80 { return "High Morale" }
        if moralePercent >= 50 { return "Content" }
        if moralePercent >= 30 { return "Fatigued" }
        return "Critical Burnout"
    }
}
