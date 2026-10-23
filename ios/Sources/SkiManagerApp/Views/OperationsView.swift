import Foundation
import SwiftUI

public struct OperationsView: View {
    @Environment(ResortStore.self) private var store
    
    @State private var selectedSegment: Int = 0 // 0: Staff, 1: Tickets, 2: Bank & Ledger
    @State private var showingHireSheet: Bool = false
    
    public init() {}
    
    public var body: some View {
        NavigationStack {
            VStack(spacing: 0) {
                // Top Stats Bar
                ResortStatsBarView()
                    .padding(.horizontal, 16)
                    .padding(.top, 8)
                    .padding(.bottom, 6)
                
                // Mode Selector Bar
                Picker("Operations Mode", selection: $selectedSegment) {
                    Label("Staff (\(store.resort.staff.count))", systemImage: "person.3.fill").tag(0)
                    Label("Tickets", systemImage: "ticket.fill").tag(1)
                    Label("Bank", systemImage: "building.columns.fill").tag(2)
                }
                .pickerStyle(.segmented)
                .padding(.horizontal, 16)
                .padding(.bottom, 8)
                
                if selectedSegment == 0 {
                    // TAB 0: Staff Roster
                    ScrollView {
                        VStack(spacing: 16) {
                            HStack {
                                Text("Employee Roster (\(store.resort.staff.count))")
                                    .font(.headline)
                                Spacer()
                                Button {
                                    showingHireSheet = true
                                } label: {
                                    Label("Hire Staff", systemImage: "plus.circle.fill")
                                        .font(.subheadline.weight(.semibold))
                                        .foregroundStyle(AlpineTheme.glacierBlue)
                                }
                            }
                            .padding(.horizontal, 16)
                            
                            LazyVStack(spacing: 10) {
                                ForEach(store.resort.staff) { member in
                                    StaffMemberCard(member: member)
                                }
                            }
                            .padding(.horizontal, 16)
                        }
                        .padding(.vertical, 8)
                    }
                } else if selectedSegment == 1 {
                    // TAB 1: Lift Tickets Pricing & Visitor Demand
                    ScrollView {
                        VStack(spacing: 16) {
                            VStack(alignment: .leading, spacing: 14) {
                                Text("Lift Ticket Dynamic Pricing")
                                    .font(.headline)
                                
                                Text("Adjust ticket rates to balance maximum gross margin against visitor volume.")
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                                
                                Divider().background(Color.white.opacity(0.12))
                                
                                // Adult Price Slider
                                VStack(alignment: .leading, spacing: 6) {
                                    HStack {
                                        Text("Adult Full-Day Pass")
                                            .font(.subheadline.weight(.semibold))
                                        Spacer()
                                        Text("€\(store.resort.ticketPriceAdult)")
                                            .font(.title3.weight(.bold))
                                            .foregroundStyle(AlpineTheme.glacierBlue)
                                            .monospacedDigit()
                                    }
                                    Slider(
                                        value: Binding(
                                            get: { Double(store.resort.ticketPriceAdult) },
                                            set: { store.setTicketPrices(adult: Int($0), child: store.resort.ticketPriceChild) }
                                        ),
                                        in: 25...150,
                                        step: 1
                                    )
                                    .tint(AlpineTheme.glacierBlue)
                                }
                                
                                // Child Price Slider
                                VStack(alignment: .leading, spacing: 6) {
                                    HStack {
                                        Text("Youth / Senior Pass")
                                            .font(.subheadline.weight(.semibold))
                                        Spacer()
                                        Text("€\(store.resort.ticketPriceChild)")
                                            .font(.title3.weight(.bold))
                                            .foregroundStyle(AlpineTheme.peakAmber)
                                            .monospacedDigit()
                                    }
                                    Slider(
                                        value: Binding(
                                            get: { Double(store.resort.ticketPriceChild) },
                                            set: { store.setTicketPrices(adult: store.resort.ticketPriceAdult, child: Int($0)) }
                                        ),
                                        in: 15...90,
                                        step: 1
                                    )
                                    .tint(AlpineTheme.peakAmber)
                                }
                                
                                Divider().background(Color.white.opacity(0.12))
                                
                                HStack {
                                    Text("Est. Daily Ticket Yield")
                                        .font(.caption)
                                        .foregroundStyle(.secondary)
                                    Spacer()
                                    Text("€\(store.resort.dailyTicketRevenue.formatted())/day")
                                        .font(.subheadline.weight(.bold))
                                        .foregroundStyle(AlpineTheme.forestGreen)
                                        .monospacedDigit()
                                }
                            }
                            .alpineGlassCard()
                            .padding(.horizontal, 16)
                        }
                        .padding(.vertical, 8)
                    }
                } else {
                    // TAB 2: Commercial Bank & Transactions
                    ScrollView {
                        VStack(spacing: 16) {
                            // Bank Loan Card
                            VStack(alignment: .leading, spacing: 12) {
                                HStack {
                                    Label("Alpine Commercial Credit", systemImage: "building.columns.fill")
                                        .font(.headline)
                                    Spacer()
                                    Text("0.3% Daily Rate")
                                        .font(.caption2.weight(.bold))
                                        .foregroundStyle(AlpineTheme.peakAmber)
                                }
                                
                                HStack {
                                    VStack(alignment: .leading, spacing: 2) {
                                        Text("Outstanding Loan")
                                            .font(.caption2)
                                            .foregroundStyle(.secondary)
                                        Text("€\(store.resort.outstandingLoan.formatted())")
                                            .font(.title2.weight(.bold))
                                            .foregroundStyle(store.resort.outstandingLoan > 0 ? AlpineTheme.avalancheRed : AlpineTheme.forestGreen)
                                            .monospacedDigit()
                                    }
                                    Spacer()
                                    VStack(alignment: .trailing, spacing: 2) {
                                        Text("Daily Interest")
                                            .font(.caption2)
                                            .foregroundStyle(.secondary)
                                        Text("-€\(store.resort.dailyLoanInterest)/day")
                                            .font(.subheadline.weight(.semibold))
                                            .foregroundStyle(AlpineTheme.avalancheRed)
                                            .monospacedDigit()
                                    }
                                }
                                
                                HStack(spacing: 10) {
                                    Button {
                                        store.takeLoan(amount: 50000)
                                    } label: {
                                        Label("Borrow €50k", systemImage: "plus.circle.fill")
                                            .font(.caption.weight(.bold))
                                            .frame(maxWidth: .infinity)
                                            .padding(.vertical, 10)
                                            .background { Capsule().fill(AlpineTheme.glacierBlue) }
                                            .foregroundStyle(.black)
                                    }
                                    
                                    Button {
                                        _ = store.repayLoan(amount: 50000)
                                    } label: {
                                        Label("Repay €50k", systemImage: "minus.circle.fill")
                                            .font(.caption.weight(.bold))
                                            .frame(maxWidth: .infinity)
                                            .padding(.vertical, 10)
                                            .background { Capsule().fill(AlpineTheme.forestGreen.opacity(0.25)) }
                                            .foregroundStyle(AlpineTheme.forestGreen)
                                    }
                                    .disabled(store.resort.outstandingLoan <= 0 || store.resort.cash < 50000)
                                }
                            }
                            .alpineGlassCard()
                            .padding(.horizontal, 16)
                            
                            // Recent Transactions
                            VStack(alignment: .leading, spacing: 10) {
                                Text("Recent Transactions")
                                    .font(.headline)
                                    .padding(.horizontal, 16)
                                
                                LazyVStack(spacing: 8) {
                                    ForEach(store.resort.transactions.prefix(20)) { tx in
                                        HStack {
                                            VStack(alignment: .leading, spacing: 2) {
                                                Text(tx.title)
                                                    .font(.caption.weight(.semibold))
                                                Text("Game Day \(tx.gameDay)")
                                                    .font(.caption2)
                                                    .foregroundStyle(.secondary)
                                            }
                                            Spacer()
                                            Text((tx.amount >= 0 ? "+€" : "-€") + "\(abs(tx.amount).formatted())")
                                                .font(.caption.weight(.bold))
                                                .foregroundStyle(tx.amount >= 0 ? AlpineTheme.forestGreen : AlpineTheme.avalancheRed)
                                                .monospacedDigit()
                                        }
                                        .padding(10)
                                        .background {
                                            RoundedRectangle(cornerRadius: 12, style: .continuous)
                                                .fill(.ultraThinMaterial)
                                        }
                                        .padding(.horizontal, 16)
                                    }
                                }
                            }
                        }
                        .padding(.vertical, 8)
                    }
                }
            }
            .navigationTitle("Operations")
            .navigationBarTitleDisplayMode(.inline)
            .sheet(isPresented: $showingHireSheet) {
                HireStaffSheet()
                    .presentationDetents([.medium, .large])
                    .presentationDragIndicator(.visible)
            }
        }
    }
}

public struct StaffMemberCard: View {
    @Environment(ResortStore.self) private var store
    public let member: StaffMember
    
    public var body: some View {
        VStack(spacing: 10) {
            HStack(spacing: 12) {
                ZStack {
                    RoundedRectangle(cornerRadius: 10, style: .continuous)
                        .fill(AlpineTheme.forestGreen.opacity(0.18))
                        .frame(width: 40, height: 40)
                    
                    Image(systemName: member.role.sfSymbol)
                        .foregroundStyle(AlpineTheme.forestGreen)
                        .font(.body.weight(.bold))
                }
                
                VStack(alignment: .leading, spacing: 2) {
                    Text(member.name)
                        .font(.subheadline.weight(.semibold))
                    Text(member.role.rawValue)
                        .font(.caption2.weight(.bold))
                        .foregroundStyle(AlpineTheme.glacierBlue)
                }
                
                Spacer()
                
                VStack(alignment: .trailing, spacing: 2) {
                    Text("€\(member.salaryPerDay)/day")
                        .font(.caption.weight(.bold))
                        .monospacedDigit()
                    Text("\(member.moralePercent)% Morale")
                        .font(.caption2)
                        .foregroundStyle(member.moralePercent > 70 ? AlpineTheme.forestGreen : AlpineTheme.peakAmber)
                }
            }
            
            Divider().background(Color.white.opacity(0.1))
            
            HStack {
                Text(member.role.roleDescription)
                    .font(.caption2)
                    .foregroundStyle(.secondary)
                    .lineLimit(1)
                
                Spacer()
                
                Button {
                    store.boostStaffMorale(id: member.id)
                } label: {
                    Text("Bonus (€350)")
                        .font(.caption2.weight(.bold))
                        .padding(.horizontal, 8)
                        .padding(.vertical, 4)
                        .background { Capsule().fill(AlpineTheme.glacierBlue.opacity(0.2)) }
                        .foregroundStyle(AlpineTheme.glacierBlue)
                }
                .disabled(store.resort.cash < 350 || member.moralePercent >= 100)
            }
        }
        .padding(12)
        .background {
            RoundedRectangle(cornerRadius: 14, style: .continuous)
                .fill(.ultraThinMaterial)
        }
    }
}

public struct HireStaffSheet: View {
    @Environment(ResortStore.self) private var store
    @Environment(\.dismiss) private var dismiss
    
    @State private var staffName: String = ""
    @State private var selectedRole: StaffRole = .skiPatrol
    
    public init() {}
    
    public var body: some View {
        NavigationStack {
            Form {
                Section("Candidate Details") {
                    TextField("Full Name", text: $staffName)
                    Picker("Role", selection: $selectedRole) {
                        ForEach(StaffRole.allCases) { role in
                            Text(role.rawValue).tag(role)
                        }
                    }
                }
                
                Section("Compensation") {
                    HStack {
                        Text("Daily Salary")
                        Spacer()
                        Text("€\(selectedRole.baseSalary)/day")
                            .fontWeight(.semibold)
                            .monospacedDigit()
                    }
                    Text(selectedRole.roleDescription)
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
            }
            .navigationTitle("Hire Mountain Crew")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Cancel") { dismiss() }
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Hire") {
                        let finalName = staffName.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
                            ? "\(selectedRole.rawValue) Specialist"
                            : staffName.trimmingCharacters(in: .whitespacesAndNewlines)
                        if store.hireStaff(name: finalName, role: selectedRole) {
                            dismiss()
                        }
                    }
                    .fontWeight(.bold)
                }
            }
        }
    }
}
