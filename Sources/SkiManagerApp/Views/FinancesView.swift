import SwiftUI

public struct FinancesView: View {
    @Environment(ResortStore.self) private var store
    
    @State private var showingLoanAlert: Bool = false
    @State private var loanAlertMessage: String = ""
    
    public init() {}
    
    public var body: some View {
        NavigationStack {
            ScrollView {
                VStack(spacing: 20) {
                    // Treasury Balance Hero Card
                    VStack(alignment: .leading, spacing: 14) {
                        HStack {
                            VStack(alignment: .leading, spacing: 4) {
                                Text("Resort Treasury Balance")
                                    .font(.subheadline)
                                    .foregroundStyle(.secondary)
                                
                                Text("€\(store.resort.cash.formatted())")
                                    .font(.system(size: 38, weight: .black, design: .rounded))
                                    .monospacedDigit()
                                    .foregroundStyle(.primary)
                            }
                            
                            Spacer()
                            
                            // Genepis Premium Currency
                            HStack(spacing: 6) {
                                Image(systemName: "sparkles")
                                    .foregroundStyle(AlpineTheme.peakAmber)
                                Text("\(store.resort.genepis)")
                                    .font(.headline.weight(.bold))
                                    .monospacedDigit()
                            }
                            .padding(.horizontal, 12)
                            .padding(.vertical, 8)
                            .background(Capsule().fill(AlpineTheme.peakAmber.opacity(0.15)))
                        }
                        
                        Divider()
                        
                        HStack {
                            Text("Estimated Daily Net:")
                                .font(.subheadline)
                                .foregroundStyle(.secondary)
                            
                            Spacer()
                            
                            let net = store.resort.netDailyProfit
                            Text("\(net >= 0 ? "+" : "-")€\(abs(net).formatted())/day")
                                .font(.headline.weight(.bold))
                                .monospacedDigit()
                                .foregroundStyle(net >= 0 ? AlpineTheme.forestGreen : AlpineTheme.avalancheRed)
                        }
                    }
                    .alpineGlassCard()
                    .padding(.horizontal, 16)
                    
                    // Lift Ticket Pricing Controller
                    VStack(alignment: .leading, spacing: 14) {
                        Text("Dynamic Lift Ticket Pricing")
                            .font(.headline)
                            .foregroundStyle(.primary)
                        
                        VStack(spacing: 12) {
                            // Adult Ticket Stepper
                            HStack {
                                VStack(alignment: .leading, spacing: 2) {
                                    Text("Adult Full Day Pass")
                                        .font(.subheadline.weight(.semibold))
                                    Text("Ages 13–64")
                                        .font(.caption2)
                                        .foregroundStyle(.secondary)
                                }
                                
                                Spacer()
                                
                                Text("€\(store.resort.ticketPriceAdult)")
                                    .font(.title3.weight(.bold))
                                    .monospacedDigit()
                                    .frame(width: 60, alignment: .trailing)
                                
                                Stepper("", value: Binding(
                                    get: { store.resort.ticketPriceAdult },
                                    set: { store.setTicketPrices(adult: $0, child: store.resort.ticketPriceChild) }
                                ), in: 30...220, step: 5)
                                .labelsHidden()
                            }
                            
                            Divider()
                            
                            // Child Ticket Stepper
                            HStack {
                                VStack(alignment: .leading, spacing: 2) {
                                    Text("Child / Senior Day Pass")
                                        .font(.subheadline.weight(.semibold))
                                    Text("Ages 6–12 & 65+")
                                        .font(.caption2)
                                        .foregroundStyle(.secondary)
                                }
                                
                                Spacer()
                                
                                Text("€\(store.resort.ticketPriceChild)")
                                    .font(.title3.weight(.bold))
                                    .monospacedDigit()
                                    .frame(width: 60, alignment: .trailing)
                                
                                Stepper("", value: Binding(
                                    get: { store.resort.ticketPriceChild },
                                    set: { store.setTicketPrices(adult: store.resort.ticketPriceAdult, child: $0) }
                                ), in: 15...120, step: 5)
                                .labelsHidden()
                            }
                        }
                        
                        Text("Higher ticket prices increase revenue per skier but reduce total attendance if mountain capacity is exceeded.")
                            .font(.caption)
                            .foregroundStyle(.secondary)
                    }
                    .alpineGlassCard()
                    .padding(.horizontal, 16)
                    
                    // Daily Income vs Expenses Itemization
                    VStack(alignment: .leading, spacing: 14) {
                        Text("Daily Budget Breakdown")
                            .font(.headline)
                            .foregroundStyle(.primary)
                        
                        VStack(spacing: 10) {
                            // Revenues
                            FinanceRow(label: "Lift Ticket Sales", amount: store.resort.dailyTicketRevenue, isPositive: true)
                            FinanceRow(label: "Lodge & Dining Receipts", amount: store.resort.dailyLodgeRevenue, isPositive: true)
                            
                            Divider()
                            
                            // Expenses
                            FinanceRow(label: "Staff Payroll", amount: store.resort.dailyStaffExpenses, isPositive: false)
                            FinanceRow(label: "Lift Fleet Maintenance", amount: store.resort.dailyLiftMaintenance, isPositive: false)
                            FinanceRow(label: "Slope Grooming Upkeep", amount: store.resort.dailySlopeUpkeep, isPositive: false)
                            FinanceRow(label: "Commercial Building Upkeep", amount: store.resort.dailyBuildingUpkeep, isPositive: false)
                            if store.resort.dailyLoanInterest > 0 {
                                FinanceRow(label: "Bank Loan Interest", amount: store.resort.dailyLoanInterest, isPositive: false)
                            }
                        }
                    }
                    .alpineGlassCard()
                    .padding(.horizontal, 16)
                    
                    // Commercial Bank Loan Center
                    VStack(alignment: .leading, spacing: 14) {
                        HStack {
                            VStack(alignment: .leading, spacing: 2) {
                                Text("Alpine Commercial Bank")
                                    .font(.headline)
                                Text("Active Loan Facility")
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                            }
                            
                            Spacer()
                            
                            Image(systemName: "building.columns.fill")
                                .font(.title2)
                                .foregroundStyle(AlpineTheme.glacierBlue)
                        }
                        
                        HStack {
                            VStack(alignment: .leading, spacing: 2) {
                                Text("Outstanding Principal")
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                                Text("€\(store.resort.outstandingLoan.formatted())")
                                    .font(.title3.weight(.bold))
                                    .monospacedDigit()
                            }
                            
                            Spacer()
                            
                            VStack(alignment: .trailing, spacing: 2) {
                                Text("Daily Interest (0.3%)")
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                                Text("€\(store.resort.dailyLoanInterest.formatted())/day")
                                    .font(.subheadline.weight(.semibold))
                                    .monospacedDigit()
                                    .foregroundStyle(AlpineTheme.avalancheRed)
                            }
                        }
                        
                        HStack(spacing: 12) {
                            Button("Borrow €50,000") {
                                store.takeLoan(amount: 50000)
                                loanAlertMessage = "Disbursed €50,000 to resort treasury."
                                showingLoanAlert = true
                            }
                            .buttonStyle(AlpineGlassButtonStyle())
                            .frame(maxWidth: .infinity)
                            
                            Button("Repay €50,000") {
                                let success = store.repayLoan(amount: 50000)
                                loanAlertMessage = success ? "Repaid €50,000 principal to Alpine Bank." : "Insufficient funds to repay loan."
                                showingLoanAlert = true
                            }
                            .buttonStyle(AlpineGlassButtonStyle(isDestructive: false))
                            .frame(maxWidth: .infinity)
                            .disabled(store.resort.outstandingLoan <= 0 || store.resort.cash < 50000)
                        }
                    }
                    .alpineGlassCard()
                    .padding(.horizontal, 16)
                    
                    // Recent Financial Transactions
                    VStack(alignment: .leading, spacing: 12) {
                        Text("Ledger History")
                            .font(.headline)
                            .foregroundStyle(.primary)
                            .padding(.horizontal, 16)
                        
                        LazyVStack(spacing: 8) {
                            ForEach(store.resort.transactions.prefix(15)) { tx in
                                HStack {
                                    Image(systemName: tx.category.sfSymbol)
                                        .font(.caption)
                                        .foregroundStyle(tx.category.tintColor)
                                        .frame(width: 24)
                                    
                                    VStack(alignment: .leading, spacing: 2) {
                                        Text(tx.title)
                                            .font(.subheadline.weight(.medium))
                                            .lineLimit(1)
                                        Text("Day \(tx.gameDay) • \(tx.category.rawValue)")
                                            .font(.caption2)
                                            .foregroundStyle(.secondary)
                                    }
                                    
                                    Spacer()
                                    
                                    Text(tx.formattedAmount)
                                        .font(.subheadline.weight(.bold))
                                        .foregroundStyle(tx.category.tintColor)
                                        .monospacedDigit()
                                }
                                .padding(12)
                                .background {
                                    RoundedRectangle(cornerRadius: 14, style: .continuous)
                                        .fill(.ultraThinMaterial)
                                }
                            }
                        }
                        .padding(.horizontal, 16)
                    }
                }
                .padding(.vertical, 16)
            }
            .navigationTitle("Finances")
            .navigationBarTitleDisplayMode(.large)
            .alert("Bank Notice", isPresented: $showingLoanAlert) {
                Button("OK", role: .cancel) {}
            } message: {
                Text(loanAlertMessage)
            }
        }
    }
}

// MARK: - Finance Row Subview

public struct FinanceRow: View {
    public let label: String
    public let amount: Int
    public let isPositive: Bool
    
    public var body: some View {
        HStack {
            Text(label)
                .font(.subheadline)
                .foregroundStyle(.secondary)
            
            Spacer()
            
            Text("\(isPositive ? "+" : "-")€\(amount.formatted())")
                .font(.subheadline.weight(.semibold))
                .monospacedDigit()
                .foregroundStyle(isPositive ? AlpineTheme.forestGreen : .primary)
        }
    }
}
