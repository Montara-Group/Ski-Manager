package net.skimanager.app.ui.game

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.EmojiEvents
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.LockOpen
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.google.gson.JsonObject
import com.google.gson.JsonParser
import kotlinx.coroutines.launch
import net.skimanager.app.BuildConfig
import net.skimanager.app.api.ApiResult
import net.skimanager.app.api.bool
import net.skimanager.app.api.int
import net.skimanager.app.api.objects
import net.skimanager.app.api.str
import net.skimanager.app.api.strOrNull
import net.skimanager.app.ui.AppState
import net.skimanager.app.ui.common.EmptyText
import net.skimanager.app.ui.common.Figure
import net.skimanager.app.ui.common.Loaded
import net.skimanager.app.ui.common.Panel
import net.skimanager.app.ui.common.PrimaryButton
import net.skimanager.app.ui.common.ScreenColumn
import net.skimanager.app.ui.common.StatusBadge
import net.skimanager.app.ui.theme.LocalSkiColors

@Composable
fun AchievementsScreen(app: AppState) {
    Loaded(Unit, {
        val res = app.api.get("achievements")
        if (res is ApiResult.Failed && res.status == 404 && BuildConfig.DEBUG) {
            ApiResult.Ok(sampleAchievementsData(), null)
        } else {
            res
        }
    }) { data, reload ->
        AchievementsContent(app, data, reload)
    }
}

@Composable
private fun AchievementsContent(app: AppState, data: JsonObject, reload: () -> Unit) {
    val completed = data.int("completed")
    val total = data.int("total")
    val readyCount = data.int("readyCount")
    val readyReward = data.str("readyReward")
    val earned = data.str("earned")
    val lockedFeatures = data.objects("lockedFeatures")
    val groups = data.objects("groups")

    val scope = rememberCoroutineScope()
    var claimingId by remember { mutableStateOf<Int?>(null) }
    var claimingAll by remember { mutableStateOf(false) }
    var actionMessage by remember { mutableStateOf<String?>(null) }
    var filter by remember { mutableStateOf("all") }

    ScreenColumn {
        if (actionMessage != null) {
            Card(
                shape = MaterialTheme.shapes.medium,
                colors = CardDefaults.cardColors(
                    containerColor = LocalSkiColors.current.success.copy(alpha = 0.15f),
                    contentColor = LocalSkiColors.current.success,
                ),
                border = BorderStroke(1.dp, LocalSkiColors.current.success.copy(alpha = 0.3f)),
                modifier = Modifier.fillMaxWidth(),
            ) {
                Row(
                    modifier = Modifier.padding(16.dp),
                    horizontalArrangement = Arrangement.spacedBy(12.dp),
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    Icon(Icons.Filled.Check, null, tint = LocalSkiColors.current.success)
                    Text(
                        actionMessage.orEmpty(),
                        style = MaterialTheme.typography.bodyMedium,
                        fontWeight = FontWeight.Medium,
                    )
                }
            }
        }

        // Summary cards
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
            Panel(modifier = Modifier.weight(1f)) {
                Text("Completed", color = LocalSkiColors.current.muted, style = MaterialTheme.typography.labelMedium)
                Text(
                    "$completed of $total",
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.Bold,
                )
                LinearProgressIndicator(
                    progress = { if (total > 0) (completed.toFloat() / total).coerceIn(0f, 1f) else 0f },
                    modifier = Modifier.fillMaxWidth().height(6.dp),
                    strokeCap = StrokeCap.Round,
                    color = LocalSkiColors.current.warning,
                )
            }
            Panel(modifier = Modifier.weight(1f)) {
                Text("Ready to claim", color = LocalSkiColors.current.muted, style = MaterialTheme.typography.labelMedium)
                Text(
                    if (readyCount > 0) readyReward else "None",
                    color = if (readyCount > 0) LocalSkiColors.current.warning else MaterialTheme.colorScheme.onSurface,
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.Bold,
                )
                if (readyCount > 0) {
                    PrimaryButton(
                        text = "Claim all $readyCount",
                        onClick = {
                            scope.launch {
                                claimingAll = true
                                val res = app.api.post("achievements/claim-all")
                                if (res is ApiResult.Ok) {
                                    actionMessage = res.message ?: "All available rewards claimed."
                                    reload()
                                } else if (res is ApiResult.Failed && res.status == 404 && BuildConfig.DEBUG) {
                                    actionMessage = "All available rewards claimed."
                                    reload()
                                } else if (res is ApiResult.Failed) {
                                    actionMessage = res.message
                                }
                                claimingAll = false
                            }
                        },
                        enabled = !claimingAll && claimingId == null,
                        modifier = Modifier.fillMaxWidth(),
                    )
                } else {
                    Text("Nothing waiting", color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
                }
            }
        }

        Figure("Rewards earned", earned, "All seasons", valueColor = LocalSkiColors.current.success)

        if (lockedFeatures.isNotEmpty()) {
            Panel(title = "Features still to unlock") {
                Text(
                    "Claim the achievement to open the feature.",
                    color = LocalSkiColors.current.muted,
                    style = MaterialTheme.typography.bodySmall,
                )
                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    lockedFeatures.forEach { f ->
                        Card(
                            shape = MaterialTheme.shapes.medium,
                            colors = CardDefaults.cardColors(containerColor = LocalSkiColors.current.surfaceSecondary),
                            border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant),
                            modifier = Modifier.fillMaxWidth(),
                        ) {
                            Row(
                                modifier = Modifier.padding(12.dp),
                                horizontalArrangement = Arrangement.spacedBy(12.dp),
                                verticalAlignment = Alignment.CenterVertically,
                            ) {
                                Icon(Icons.Filled.Lock, null, tint = LocalSkiColors.current.muted)
                                Column(Modifier.weight(1f)) {
                                    Text(f.str("feature"), fontWeight = FontWeight.Bold, style = MaterialTheme.typography.bodyMedium)
                                    Text(
                                        "${f.str("name")}: ${f.str("description")}",
                                        color = LocalSkiColors.current.muted,
                                        style = MaterialTheme.typography.bodySmall,
                                    )
                                }
                                if (f.bool("ready")) {
                                    StatusBadge(
                                        text = "Claim to unlock",
                                        color = LocalSkiColors.current.warning,
                                        backgroundColor = LocalSkiColors.current.warning.copy(alpha = 0.15f),
                                    )
                                } else {
                                    Text(
                                        "${f.int("pct")}%",
                                        color = LocalSkiColors.current.muted,
                                        style = MaterialTheme.typography.labelMedium,
                                        fontWeight = FontWeight.Bold,
                                    )
                                }
                            }
                        }
                    }
                }
            }
        }

        // Filter chips
        val progressCount = total - completed
        val claimedCount = completed - readyCount
        val filterOptions = listOf(
            "all" to "All ($total)",
            "ready" to "Ready ($readyCount)",
            "progress" to "In progress ($progressCount)",
            "claimed" to "Done ($claimedCount)",
        )
        Row(
            modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
            horizontalArrangement = Arrangement.spacedBy(8.dp),
        ) {
            filterOptions.forEach { (key, label) ->
                FilterChip(
                    selected = filter == key,
                    onClick = { filter = key },
                    label = { Text(label, fontWeight = if (filter == key) FontWeight.Bold else FontWeight.Medium) },
                    shape = MaterialTheme.shapes.small,
                    modifier = Modifier.heightIn(min = 48.dp),
                )
            }
        }

        var anyRendered = false
        groups.forEach { group ->
            val allItems = group.objects("items")
            val filteredItems = if (filter == "all") allItems else allItems.filter { it.str("state") == filter }
            if (filteredItems.isNotEmpty()) {
                anyRendered = true
                val done = group.int("done")
                val categoryName = group.str("category")

                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        Text(
                            categoryName,
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold,
                        )
                        StatusBadge(
                            text = "$done / ${allItems.size}",
                            color = LocalSkiColors.current.muted,
                            backgroundColor = LocalSkiColors.current.surfaceSecondary,
                        )
                    }

                    filteredItems.forEach { item ->
                        val state = item.str("state")
                        val isReady = state == "ready"
                        val isClaimed = state == "claimed"
                        val id = item.int("id")
                        val reward = item.strOrNull("reward")
                        val unlocks = item.strOrNull("unlocks")

                        Card(
                            shape = MaterialTheme.shapes.large,
                            colors = CardDefaults.cardColors(
                                containerColor = MaterialTheme.colorScheme.surfaceContainer,
                                contentColor = MaterialTheme.colorScheme.onSurface,
                            ),
                            border = BorderStroke(
                                if (isReady) 2.dp else 1.dp,
                                if (isReady) LocalSkiColors.current.warning else MaterialTheme.colorScheme.outlineVariant,
                            ),
                            modifier = Modifier.fillMaxWidth(),
                        ) {
                            Column(
                                modifier = Modifier.padding(16.dp),
                                verticalArrangement = Arrangement.spacedBy(12.dp),
                            ) {
                                Row(
                                    horizontalArrangement = Arrangement.spacedBy(14.dp),
                                    verticalAlignment = Alignment.Top,
                                ) {
                                    Surface(
                                        shape = MaterialTheme.shapes.medium,
                                        color = when {
                                            isClaimed -> LocalSkiColors.current.success.copy(alpha = 0.12f)
                                            isReady -> LocalSkiColors.current.warning.copy(alpha = 0.15f)
                                            else -> LocalSkiColors.current.surfaceSecondary
                                        },
                                        modifier = Modifier.size(44.dp),
                                    ) {
                                        Box(contentAlignment = Alignment.Center) {
                                            Icon(
                                                Icons.Filled.EmojiEvents,
                                                null,
                                                tint = when {
                                                    isClaimed -> LocalSkiColors.current.success
                                                    isReady -> LocalSkiColors.current.warning
                                                    else -> LocalSkiColors.current.muted
                                                },
                                                modifier = Modifier.size(24.dp),
                                            )
                                        }
                                    }

                                    Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically,
                                        ) {
                                            Text(
                                                item.str("name"),
                                                fontWeight = FontWeight.Bold,
                                                style = MaterialTheme.typography.titleMedium,
                                                modifier = Modifier.weight(1f),
                                            )
                                            if (reward != null) {
                                                Text(
                                                    reward,
                                                    color = LocalSkiColors.current.warning,
                                                    fontWeight = FontWeight.Bold,
                                                    style = MaterialTheme.typography.labelLarge,
                                                )
                                            }
                                        }

                                        Text(
                                            item.str("description"),
                                            color = LocalSkiColors.current.muted,
                                            style = MaterialTheme.typography.bodySmall,
                                        )

                                        if (state == "progress") {
                                            val pct = item.int("pct")
                                            Column(
                                                modifier = Modifier.padding(top = 4.dp),
                                                verticalArrangement = Arrangement.spacedBy(4.dp),
                                            ) {
                                                LinearProgressIndicator(
                                                    progress = { (pct / 100f).coerceIn(0f, 1f) },
                                                    modifier = Modifier.fillMaxWidth().height(6.dp),
                                                    strokeCap = StrokeCap.Round,
                                                    color = MaterialTheme.colorScheme.primary,
                                                )
                                                Row(
                                                    modifier = Modifier.fillMaxWidth(),
                                                    horizontalArrangement = Arrangement.SpaceBetween,
                                                ) {
                                                    Text(
                                                        item.str("progress"),
                                                        color = LocalSkiColors.current.muted,
                                                        style = MaterialTheme.typography.bodySmall,
                                                    )
                                                    Text(
                                                        "$pct%",
                                                        color = LocalSkiColors.current.muted,
                                                        style = MaterialTheme.typography.bodySmall,
                                                        fontWeight = FontWeight.Bold,
                                                    )
                                                }
                                            }
                                        }

                                        Row(
                                            modifier = Modifier.fillMaxWidth().padding(top = 4.dp),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically,
                                        ) {
                                            if (unlocks != null) {
                                                Row(
                                                    horizontalArrangement = Arrangement.spacedBy(4.dp),
                                                    verticalAlignment = Alignment.CenterVertically,
                                                ) {
                                                    Icon(
                                                        if (isClaimed) Icons.Filled.LockOpen else Icons.Filled.Lock,
                                                        null,
                                                        tint = LocalSkiColors.current.muted,
                                                        modifier = Modifier.size(14.dp),
                                                    )
                                                    Text(
                                                        if (isClaimed) "Unlocked: $unlocks" else "Unlocks: $unlocks",
                                                        color = LocalSkiColors.current.muted,
                                                        style = MaterialTheme.typography.bodySmall,
                                                    )
                                                }
                                            } else {
                                                Box {}
                                            }

                                            when {
                                                isReady -> {
                                                    PrimaryButton(
                                                        text = "Claim",
                                                        onClick = {
                                                            scope.launch {
                                                                claimingId = id
                                                                val res = app.api.post("achievements/claim/$id")
                                                                if (res is ApiResult.Ok) {
                                                                    actionMessage = res.message ?: "Claimed ${item.str("name")}!"
                                                                    reload()
                                                                } else if (res is ApiResult.Failed && res.status == 404 && BuildConfig.DEBUG) {
                                                                    actionMessage = "Claimed ${item.str("name")}!"
                                                                    reload()
                                                                } else if (res is ApiResult.Failed) {
                                                                    actionMessage = res.message
                                                                }
                                                                claimingId = null
                                                            }
                                                        },
                                                        enabled = claimingId == null && !claimingAll,
                                                    )
                                                }
                                                isClaimed -> {
                                                    StatusBadge(
                                                        text = "Claimed",
                                                        color = LocalSkiColors.current.success,
                                                        backgroundColor = LocalSkiColors.current.success.copy(alpha = 0.15f),
                                                    )
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!anyRendered) {
            EmptyText(
                if (filter == "ready") "No rewards are waiting. Finished goals appear here to claim."
                else "No achievements match this filter."
            )
        }
    }
}

private fun sampleAchievementsData(): JsonObject {
    return JsonParser.parseString("""
    {
        "completed": 6,
        "total": 45,
        "readyCount": 2,
        "readyReward": "$10,000",
        "earned": "$25,000",
        "lockedFeatures": [
            {
                "feature": "Night Skiing",
                "name": "Night Owl",
                "description": "Keep slopes open after dark",
                "ready": false,
                "pct": 60
            },
            {
                "feature": "Terrain Parks",
                "name": "Park Builder",
                "description": "Construct your first terrain park",
                "ready": true,
                "pct": 100
            }
        ],
        "groups": [
            {
                "category": "Resort",
                "done": 2,
                "items": [
                    {
                        "id": 1,
                        "name": "First Lift",
                        "description": "Build your first chairlift",
                        "icon": "fa-solid fa-chairlift",
                        "reward": "$5,000",
                        "reward_amount": 5000,
                        "state": "ready",
                        "pct": 100,
                        "progress": "1 / 1",
                        "unlocks": null
                    },
                    {
                        "id": 2,
                        "name": "Trail Master",
                        "description": "Build 5 ski slopes",
                        "icon": "fa-solid fa-mountain",
                        "reward": "$10,000",
                        "reward_amount": 10000,
                        "state": "progress",
                        "pct": 40,
                        "progress": "2 / 5",
                        "unlocks": null
                    },
                    {
                        "id": 3,
                        "name": "Pioneer",
                        "description": "Establish your resort",
                        "icon": "fa-solid fa-flag",
                        "reward": "$2,500",
                        "reward_amount": 2500,
                        "state": "claimed",
                        "pct": 100,
                        "progress": "1 / 1",
                        "unlocks": null
                    }
                ]
            },
            {
                "category": "Staff",
                "done": 2,
                "items": [
                    {
                        "id": 10,
                        "name": "First Hire",
                        "description": "Hire your first staff member",
                        "icon": "fa-solid fa-user-plus",
                        "reward": "$5,000",
                        "reward_amount": 5000,
                        "state": "ready",
                        "pct": 100,
                        "progress": "1 / 1",
                        "unlocks": null
                    },
                    {
                        "id": 11,
                        "name": "Full Crew",
                        "description": "Employ 10 active team members",
                        "icon": "fa-solid fa-users",
                        "reward": "$15,000",
                        "reward_amount": 15000,
                        "state": "progress",
                        "pct": 30,
                        "progress": "3 / 10",
                        "unlocks": null
                    }
                ]
            },
            {
                "category": "Safety & rules",
                "done": 1,
                "items": [
                    {
                        "id": 20,
                        "name": "Safety First",
                        "description": "Assign ski patrol to all slopes",
                        "icon": "fa-solid fa-shield-halved",
                        "reward": "$8,000",
                        "reward_amount": 8000,
                        "state": "progress",
                        "pct": 50,
                        "progress": "1 / 2",
                        "unlocks": "Safety Certification"
                    }
                ]
            }
        ]
    }
    """).asJsonObject
}
