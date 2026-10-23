package net.skimanager.app.model

import com.google.gson.annotations.SerializedName

enum class StaffRole(
    val label: String,
    val description: String,
    val defaultSalary: Int
) {
    @SerializedName("patrol")
    SKI_PATROL("Ski Patrol / Pisteur", "Ensures slope safety, avalanche control and rescue operations", 150),

    @SerializedName("groomer")
    GROOMER("Snowcat Groomer Driver", "Operates PistenBully fleet to prepare corduroy trails", 110),

    @SerializedName("mechanic")
    MECHANIC("Lift Technician", "Maintains cable ropeways, chairlifts and motor gearboxes", 130),

    @SerializedName("instructor")
    INSTRUCTOR("Ski / Snowboard Instructor", "Leads lessons, driving customer satisfaction and reputation", 95),

    @SerializedName("admin")
    ADMINISTRATIVE("Resort Marketing & Host", "Boosts resort exposure and manages visitor logistics", 125)
}

data class StaffMember(
    val id: String = java.util.UUID.randomUUID().toString(),
    val name: String,
    val role: StaffRole,
    var salaryPerDay: Int = role.defaultSalary,
    var skillStars: Int = 4,
    var moralePercent: Int = 90
)
