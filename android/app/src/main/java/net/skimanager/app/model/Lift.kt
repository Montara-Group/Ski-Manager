package net.skimanager.app.model

import com.google.gson.annotations.SerializedName

enum class LiftType(
    val label: String,
    val baseCapacity: Int,
    val baseDailyMaintenance: Int,
    val baseConstructionCost: Long
) {
    @SerializedName("surfaceTow")
    SURFACE_TOW("Surface Platter Tow", 600, 90, 85000),

    @SerializedName("fixedDouble")
    FIXED_DOUBLE("Fixed 2-Seater Chair", 1100, 160, 220000),

    @SerializedName("fixedQuad")
    FIXED_QUAD("Fixed 4-Seater Chair", 1800, 210, 320000),

    @SerializedName("expressQuad")
    EXPRESS_QUAD("High-Speed Detachable Quad", 2400, 320, 540000),

    @SerializedName("sixPackExpress")
    SIX_PACK_EXPRESS("6-Passenger High-Speed Express", 3000, 410, 780000),

    @SerializedName("gondola8")
    GONDOLA_8("8-Passenger Panorama Gondola", 2800, 520, 1150000),

    @SerializedName("aerialTramway")
    AERIAL_TRAMWAY("Aerial Peak Tramway", 1200, 680, 1850000)
}

enum class LiftStatus(val label: String) {
    @SerializedName("running")
    RUNNING("Running"),

    @SerializedName("maintenance")
    MAINTENANCE("Maintenance"),

    @SerializedName("closed")
    CLOSED("Closed")
}

data class Lift(
    val id: String = java.util.UUID.randomUUID().toString(),
    val name: String,
    val liftType: LiftType,
    val sectorNumber: Int = 1,
    val lengthMeters: Int,
    val verticalRiseMeters: Int,
    var status: LiftStatus = LiftStatus.RUNNING,
    var conditionPercent: Int = 100
) {
    val hourlyCapacity: Int get() = liftType.baseCapacity
    val dailyMaintenanceCost: Int get() = liftType.baseDailyMaintenance
}
