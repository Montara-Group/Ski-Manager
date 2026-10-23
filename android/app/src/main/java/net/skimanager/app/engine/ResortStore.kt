package net.skimanager.app.engine

import android.content.Context
import com.google.gson.Gson
import com.google.gson.GsonBuilder
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import net.skimanager.app.model.*
import net.skimanager.app.network.ClientSyncPayload
import net.skimanager.app.network.SkiManagerApiClient
import java.io.File
import kotlin.random.Random

class ResortStore(
    private val context: Context,
    private val scope: CoroutineScope
) {
    private val apiClient = SkiManagerApiClient()
    private val saveFileName = "ski_manager_resort_v4.json"
    private val gson: Gson = GsonBuilder().setPrettyPrinting().create()

    private val _resort = MutableStateFlow(loadOrInitializeResort())
    val resort: StateFlow<Resort> = _resort.asStateFlow()

    private val _isOnline = MutableStateFlow(true)
    val isOnline: StateFlow<Boolean> = _isOnline.asStateFlow()

    private val _isSyncing = MutableStateFlow(false)
    val isSyncing: StateFlow<Boolean> = _isSyncing.asStateFlow()

    private val _connectionStatusText = MutableStateFlow("Connecting to ski-manager.net...")
    val connectionStatusText: StateFlow<String> = _connectionStatusText.asStateFlow()

    private val _onlineLeaderboard = MutableStateFlow<List<LeaderboardEntry>>(emptyList())
    val onlineLeaderboard: StateFlow<List<LeaderboardEntry>> = _onlineLeaderboard.asStateFlow()

    private val _lastBonusAwardMessage = MutableStateFlow<String?>(null)
    val lastBonusAwardMessage: StateFlow<String?> = _lastBonusAwardMessage.asStateFlow()

    init {
        scope.launch {
            performInitialOnlineConnection()
        }
    }

    private fun loadOrInitializeResort(): Resort {
        val file = File(context.filesDir, saveFileName)
        if (file.exists()) {
            try {
                val json = file.readText()
                val loaded = gson.fromJson(json, Resort::class.java)
                if (loaded != null && loaded.realSegments.isNotEmpty()) {
                    return loaded
                }
            } catch (e: Exception) {
                e.printStackTrace()
            }
        }

        // Initialize fresh template with bundled RealMapData
        val template = Resort.makeParkCityTemplate()
        val bundled = RealMapDataLoader.loadBundledMapData(context)
        template.realSegments = bundled.segments.toMutableList()
        template.realSectors = bundled.sectors.toMutableList()
        return template
    }

    suspend fun performInitialOnlineConnection() {
        val health = apiClient.checkServerHealth()
        _isOnline.value = health.isHealthy
        _connectionStatusText.value = health.message

        val board = apiClient.fetchLeaderboard()
        if (board.isNotEmpty()) {
            _onlineLeaderboard.value = board
        }

        if (health.isHealthy) {
            syncWithLiveWebsite()
        }
    }

    fun advanceDay() {
        val current = _resort.value

        // 1. Increment Game Day
        current.gameDay += 1

        // 2. Weather Dynamics
        updateMountainWeather(current)

        // 3. Visitor Attendance Equation matching website algorithm
        val priceFactor = maxOf(0.5, 1.6 - (current.ticketPriceAdult.toDouble() / 68.0) * 0.6)
        val openSlopes = current.openSlopesCount
        val openSlopesFactor = openSlopes.toDouble() / maxOf(1.0, (openSlopes + 2).toDouble())
        val snowQualityFactor = if (current.weather.newSnowfall24hCm > 10) 1.25 else 1.0
        val repFactor = current.reputationScore.toDouble() / 100.0

        val baseVisitors = Random.nextDouble(420.0, 680.0)
        val calculated = (baseVisitors * priceFactor * openSlopesFactor * snowQualityFactor * repFactor * current.difficulty.visitorMultiplier).toInt()
        current.totalVisitorsToday = maxOf(180, calculated)

        // 4. Financial Calculations
        val ticketRev = current.dailyTicketRevenue
        val lodgeRev = current.dailyLodgeRevenue
        val staffCost = current.dailyStaffExpenses
        val liftMaint = current.dailyLiftMaintenance
        val slopeUpkeep = current.dailySlopeUpkeep
        val loanInterest = current.dailyLoanInterest

        val dayGross = ticketRev + lodgeRev
        val dayExpenses = staffCost + liftMaint + slopeUpkeep + loanInterest
        val netProfit = dayGross - dayExpenses

        current.cash += netProfit

        // 5. Append Ledger Records
        val newTx = mutableListOf<GameTransaction>()
        newTx.add(GameTransaction(title = "Lift Ticket Sales (Day ${current.gameDay})", amount = ticketRev, category = TransactionCategory.TICKET_SALES, gameDay = current.gameDay))
        if (lodgeRev > 0) {
            newTx.add(GameTransaction(title = "Lodge & Dining Receipts", amount = lodgeRev, category = TransactionCategory.BUILDING_REVENUE, gameDay = current.gameDay))
        }
        newTx.add(GameTransaction(title = "Staff Payroll Deductions", amount = -staffCost, category = TransactionCategory.STAFF_WAGES, gameDay = current.gameDay))
        newTx.add(GameTransaction(title = "Lift Fleet Electricity & Maintenance", amount = -liftMaint, category = TransactionCategory.LIFT_MAINTENANCE, gameDay = current.gameDay))
        if (slopeUpkeep > 0) {
            newTx.add(GameTransaction(title = "Slope Grooming Upkeep", amount = -slopeUpkeep, category = TransactionCategory.SLOPE_MAINTENANCE, gameDay = current.gameDay))
        }
        if (loanInterest > 0) {
            newTx.add(GameTransaction(title = "Bank Loan Interest", amount = -loanInterest, category = TransactionCategory.BANK_INTEREST, gameDay = current.gameDay))
        }

        current.transactions = (newTx + current.transactions).take(50).toMutableList()

        // 6. Wear & Tear on Real Map Lifts
        val mechanicsCount = current.staff.count { it.role == StaffRole.MECHANIC }
        current.realSegments.forEach { seg ->
            if (seg.isLift && seg.isBuilt && seg.isOpen) {
                val wear = Random.nextInt(1, 4)
                seg.conditionPercent = maxOf(25, seg.conditionPercent - wear)
                if (mechanicsCount > 0) {
                    seg.conditionPercent = minOf(100, seg.conditionPercent + (mechanicsCount * 2))
                }
            }
        }

        // 7. Slope Grooming Decay
        current.realSegments.forEach { seg ->
            if (seg.isSlope && seg.isBuilt && seg.isOpen) {
                val decay = Random.nextInt(8, 17)
                seg.groomingPercent = maxOf(15, seg.groomingPercent - decay)
            }
        }

        // 8. Groomer Durability Degradation
        current.groomerConditionPct = maxOf(20, current.groomerConditionPct - Random.nextInt(1, 4))

        // 9. Staff Morale Dynamics
        current.staff.forEach { member ->
            val delta = if (netProfit > 0) Random.nextInt(0, 3) else -Random.nextInt(1, 4)
            member.moralePercent = maxOf(25, minOf(100, member.moralePercent + delta))
        }

        _resort.value = current.copy()
        saveToDisk()
        triggerBackgroundPush()
    }

    fun claimDailyBonus(): Pair<Long, Int>? {
        val current = _resort.value
        if (!current.canClaimDailyBonus) return null

        val streak = current.dailyBonusStreak
        val cashReward = 5000L + (streak * 750L)
        val genepisReward = 5 + minOf(10, streak)

        current.cash += cashReward
        current.genepis += genepisReward
        current.lastBonusClaimDay = current.gameDay
        current.dailyBonusStreak += 1

        current.transactions.add(
            0,
            GameTransaction(
                title = "Daily Bonus Streak Day #$streak",
                amount = cashReward,
                category = TransactionCategory.TICKET_SALES,
                gameDay = current.gameDay
            )
        )

        val msg = "Claimed +€$cashReward and +$genepisReward 🌿 Génépis!"
        _lastBonusAwardMessage.value = msg

        _resort.value = current.copy()
        saveToDisk()
        triggerBackgroundPush()
        return Pair(cashReward, genepisReward)
    }

    private fun updateMountainWeather(resort: Resort) {
        val conditions = listOf(
            WeatherCondition.BLUEBIRD_POWDER,
            WeatherCondition.LIGHT_SNOW,
            WeatherCondition.SUNNY,
            WeatherCondition.PARTLY_CLOUDY,
            WeatherCondition.HEAVY_BLIZZARD,
            WeatherCondition.LIGHT_SNOW,
            WeatherCondition.BLUEBIRD_POWDER
        )
        val nextCondition = conditions.random()
        val snowAccum = when (nextCondition) {
            WeatherCondition.HEAVY_BLIZZARD -> Random.nextInt(25, 46)
            WeatherCondition.BLUEBIRD_POWDER -> Random.nextInt(15, 31)
            WeatherCondition.LIGHT_SNOW -> Random.nextInt(5, 15)
            WeatherCondition.SUNNY, WeatherCondition.PARTLY_CLOUDY -> 0
            WeatherCondition.DENSE_FOG -> Random.nextInt(2, 7)
            WeatherCondition.SPRING_SLUSH -> 0
        }
        val windKmh = when (nextCondition) {
            WeatherCondition.HEAVY_BLIZZARD -> Random.nextInt(45, 71)
            WeatherCondition.BLUEBIRD_POWDER -> Random.nextInt(10, 23)
            WeatherCondition.LIGHT_SNOW -> Random.nextInt(12, 29)
            WeatherCondition.SUNNY, WeatherCondition.PARTLY_CLOUDY -> Random.nextInt(8, 21)
            WeatherCondition.DENSE_FOG -> Random.nextInt(5, 16)
            WeatherCondition.SPRING_SLUSH -> Random.nextInt(10, 26)
        }
        val dangerLevel = when (nextCondition) {
            WeatherCondition.HEAVY_BLIZZARD -> 4
            WeatherCondition.BLUEBIRD_POWDER -> 3
            WeatherCondition.LIGHT_SNOW -> 2
            WeatherCondition.SUNNY, WeatherCondition.PARTLY_CLOUDY -> 1
            WeatherCondition.DENSE_FOG -> 2
            WeatherCondition.SPRING_SLUSH -> 1
        }
        val newTemp = if (nextCondition == WeatherCondition.SUNNY) Random.nextDouble(-2.0, 2.0) else Random.nextDouble(-9.0, -3.0)
        val newBase = maxOf(60, resort.weather.baseDepthCm + snowAccum - (if (newTemp > 0) 3 else 1))

        resort.weather = MountainWeather(
            condition = nextCondition,
            temperatureCelsius = newTemp,
            newSnowfall24hCm = snowAccum,
            baseDepthCm = newBase,
            windSpeedKmh = windKmh,
            visibilityKm = if (nextCondition == WeatherCondition.DENSE_FOG) 1.5 else (if (nextCondition == WeatherCondition.HEAVY_BLIZZARD) 3.0 else 15.0),
            avalancheDangerLevel = dangerLevel,
            isSnowmakingOptimal = newTemp <= -3.0
        )
    }

    // Segment & Building Controls
    fun toggleRealSegmentOpen(id: Int) {
        val current = _resort.value
        val seg = current.realSegments.find { it.id == id } ?: return
        seg.isOpen = !seg.isOpen
        _resort.value = current.copy()
        saveToDisk()
        triggerBackgroundPush()
    }

    fun buildRealSegment(id: Int): Boolean {
        val current = _resort.value
        val seg = current.realSegments.find { it.id == id } ?: return false
        if (seg.isBuilt) return true

        val cost = seg.constructionCost
        if (current.cash < cost) return false

        current.cash -= cost
        seg.isBuilt = true
        seg.isOpen = true

        val txTitle = if (seg.isLift) "Constructed ${seg.name}" else "Carved ${seg.name} Trail"
        current.transactions.add(
            0,
            GameTransaction(title = txTitle, amount = -cost, category = TransactionCategory.CAPITAL_INVESTMENT, gameDay = current.gameDay)
        )

        _resort.value = current.copy()
        saveToDisk()
        triggerBackgroundPush()
        return true
    }

    fun groomAllSlopes() {
        val current = _resort.value
        val groomCost = current.realSegments.count { it.isSlope && it.isBuilt } * 85L
        if (current.cash < groomCost || groomCost == 0L) return

        current.cash -= groomCost
        current.transactions.add(
            0,
            GameTransaction(title = "PistenBully 600 Corduroy Grooming Fleet", amount = -groomCost, category = TransactionCategory.SLOPE_MAINTENANCE, gameDay = current.gameDay)
        )

        current.realSegments.forEach { if (it.isSlope && it.isBuilt) it.groomingPercent = 95 }
        current.slopes.forEach {
            it.groomingPercent = 95
            it.snowQuality = SnowQuality.GROOMED_CORDUROY
        }
        current.groomerConditionPct = maxOf(20, current.groomerConditionPct - 2)

        _resort.value = current.copy()
        saveToDisk()
        triggerBackgroundPush()
    }

    fun repairGroomer(): Boolean {
        val current = _resort.value
        val cost = 3200L
        if (current.cash < cost || current.groomerConditionPct >= 100) return false

        current.cash -= cost
        current.groomerConditionPct = 100
        current.transactions.add(
            0,
            GameTransaction(title = "PistenBully 600 Factory Overhaul", amount = -cost, category = TransactionCategory.SLOPE_MAINTENANCE, gameDay = current.gameDay)
        )

        _resort.value = current.copy()
        saveToDisk()
        triggerBackgroundPush()
        return true
    }

    fun constructBuilding(name: String, type: BuildingType, sector: Int = 1): Boolean {
        val current = _resort.value
        val cost = type.baseBuildCost
        if (current.cash < cost) return false

        current.cash -= cost
        val building = ResortBuilding(name = name, buildingType = type, upgradeLevel = 1, isOpen = true, sectorNumber = sector)
        current.buildings.add(building)
        current.transactions.add(
            0,
            GameTransaction(title = "Built $name Facility", amount = -cost, category = TransactionCategory.CAPITAL_INVESTMENT, gameDay = current.gameDay)
        )

        _resort.value = current.copy()
        saveToDisk()
        triggerBackgroundPush()
        return true
    }

    fun upgradeBuilding(id: String): Boolean {
        val current = _resort.value
        val b = current.buildings.find { it.id == id } ?: return false
        val cost = b.nextUpgradeCost
        if (current.cash < cost || b.upgradeLevel >= 5) return false

        current.cash -= cost
        b.upgradeLevel += 1
        current.transactions.add(
            0,
            GameTransaction(title = "Upgraded ${b.name} to Level ${b.upgradeLevel}", amount = -cost, category = TransactionCategory.CAPITAL_INVESTMENT, gameDay = current.gameDay)
        )

        _resort.value = current.copy()
        saveToDisk()
        triggerBackgroundPush()
        return true
    }

    fun toggleBuildingOpen(id: String) {
        val current = _resort.value
        val b = current.buildings.find { it.id == id } ?: return
        b.isOpen = !b.isOpen
        _resort.value = current.copy()
        saveToDisk()
        triggerBackgroundPush()
    }

    fun hireStaff(name: String, role: StaffRole) {
        val current = _resort.value
        val member = StaffMember(name = name, role = role, skillStars = Random.nextInt(3, 6), moralePercent = 90)
        current.staff.add(member)
        _resort.value = current.copy()
        saveToDisk()
        triggerBackgroundPush()
    }

    fun fireStaff(id: String) {
        val current = _resort.value
        current.staff.removeAll { it.id == id }
        _resort.value = current.copy()
        saveToDisk()
        triggerBackgroundPush()
    }

    fun takeLoan(amount: Long) {
        val current = _resort.value
        current.outstandingLoan += amount
        current.cash += amount
        current.transactions.add(
            0,
            GameTransaction(title = "Park City Commercial Bank Loan Disbursed", amount = amount, category = TransactionCategory.CAPITAL_INVESTMENT, gameDay = current.gameDay)
        )
        _resort.value = current.copy()
        saveToDisk()
        triggerBackgroundPush()
    }

    fun repayLoan(amount: Long): Boolean {
        val current = _resort.value
        val repayAmount = minOf(amount, current.outstandingLoan)
        if (current.cash < repayAmount || repayAmount <= 0) return false

        current.cash -= repayAmount
        current.outstandingLoan -= repayAmount
        current.transactions.add(
            0,
            GameTransaction(title = "Bank Loan Principal Repayment", amount = -repayAmount, category = TransactionCategory.CAPITAL_INVESTMENT, gameDay = current.gameDay)
        )
        _resort.value = current.copy()
        saveToDisk()
        triggerBackgroundPush()
        return true
    }

    fun setTicketPrices(adult: Int, child: Int) {
        val current = _resort.value
        current.ticketPriceAdult = adult.coerceIn(20, 250)
        current.ticketPriceChild = child.coerceIn(10, 150)
        _resort.value = current.copy()
        saveToDisk()
        triggerBackgroundPush()
    }

    fun resetToDemoMountain() {
        val template = Resort.makeParkCityTemplate()
        val bundled = RealMapDataLoader.loadBundledMapData(context)
        template.realSegments = bundled.segments.toMutableList()
        template.realSectors = bundled.sectors.toMutableList()
        _resort.value = template
        saveToDisk()
        triggerBackgroundPush()
    }

    suspend fun syncWithLiveWebsite(): Boolean {
        _isSyncing.value = true
        return try {
            val apiData = apiClient.fetchResortData()
            val current = _resort.value

            if (apiData?.finances != null) {
                apiData.finances.cash?.let { current.cash = it }
                apiData.finances.daily_visitors?.let { current.totalVisitorsToday = it }
            }
            if (apiData?.weather != null) {
                apiData.weather.game_day?.let { current.gameDay = it }
                apiData.weather.temperature?.let { current.weather.temperatureCelsius = it.toDouble() }
                apiData.weather.snowfall?.let { current.weather.newSnowfall24hCm = it }
                apiData.weather.base_depth?.let { current.weather.baseDepthCm = it }
                apiData.weather.wind_speed?.let { current.weather.windSpeedKmh = it }
            }

            val board = apiClient.fetchLeaderboard()
            if (board.isNotEmpty()) {
                _onlineLeaderboard.value = board
            }

            _isOnline.value = true
            _connectionStatusText.value = "Online · Connected to ski-manager.net"
            _isSyncing.value = false
            _resort.value = current.copy()
            saveToDisk()
            true
        } catch (e: Exception) {
            _isOnline.value = false
            _connectionStatusText.value = "Offline (Cached Data)"
            _isSyncing.value = false
            false
        }
    }

    private fun triggerBackgroundPush() {
        scope.launch {
            val current = _resort.value
            val payload = ClientSyncPayload(
                cash = current.cash,
                game_day = current.gameDay,
                daily_visitors = current.totalVisitorsToday,
                ticket_price_adult = current.ticketPriceAdult,
                ticket_price_child = current.ticketPriceChild,
                built_segments_count = current.realSegments.count { it.isBuilt },
                outstanding_loan = current.outstandingLoan
            )
            apiClient.pushSync(payload)
        }
    }

    fun saveToDisk() {
        try {
            val file = File(context.filesDir, saveFileName)
            val json = gson.toJson(_resort.value)
            file.writeText(json)
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }

    fun getLocalSaveFileSizeFormatted(): String {
        val file = File(context.filesDir, saveFileName)
        if (!file.exists()) return "Bundled Template (64 KB)"
        val kb = file.length() / 1024
        return "$kb KB (Local Disk)"
    }
}
