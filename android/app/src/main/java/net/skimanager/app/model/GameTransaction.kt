package net.skimanager.app.model

import com.google.gson.annotations.SerializedName

enum class TransactionCategory(val label: String, val isExpense: Boolean) {
    @SerializedName("ticketSales")
    TICKET_SALES("Ticket Sales", false),

    @SerializedName("buildingRevenue")
    BUILDING_REVENUE("Village Commerce", false),

    @SerializedName("staffWages")
    STAFF_WAGES("Staff Payroll", true),

    @SerializedName("liftMaintenance")
    LIFT_MAINTENANCE("Lift Fleet", true),

    @SerializedName("slopeMaintenance")
    SLOPE_MAINTENANCE("Slope Grooming", true),

    @SerializedName("snowmakingEnergy")
    SNOWMAKING_ENERGY("Snowmaking Power", true),

    @SerializedName("bankInterest")
    BANK_INTEREST("Bank Interest", true),

    @SerializedName("capitalInvestment")
    CAPITAL_INVESTMENT("Capital Infrastructure", true)
}

data class GameTransaction(
    val id: String = java.util.UUID.randomUUID().toString(),
    val title: String,
    val amount: Long,
    val category: TransactionCategory,
    val gameDay: Int,
    val timestampMs: Long = System.currentTimeMillis()
)
