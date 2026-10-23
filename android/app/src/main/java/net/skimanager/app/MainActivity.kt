package net.skimanager.app

import android.os.Bundle
import android.view.HapticFeedbackConstants
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.animation.*
import androidx.compose.foundation.layout.*
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.rounded.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalView
import androidx.compose.ui.unit.dp
import androidx.lifecycle.lifecycleScope
import net.skimanager.app.engine.ResortStore
import net.skimanager.app.ui.components.ResortStatsBar
import net.skimanager.app.ui.screens.*
import net.skimanager.app.ui.theme.SkiManagerTheme

enum class NavigationTab(val label: String, val icon: ImageVector) {
    DASHBOARD("Dashboard", Icons.Rounded.Dashboard),
    TRAIL_MAP("Trail Map", Icons.Rounded.Map),
    VILLAGE("Village", Icons.Rounded.HolidayVillage),
    OPERATIONS("Operations", Icons.Rounded.Engineering),
    ONLINE("Online", Icons.Rounded.Cloud)
}

class MainActivity : ComponentActivity() {
    private lateinit var resortStore: ResortStore

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()

        resortStore = ResortStore(applicationContext, lifecycleScope)

        setContent {
            SkiManagerTheme {
                MainAppScaffold(store = resortStore)
            }
        }
    }
}

@Composable
fun MainAppScaffold(store: ResortStore) {
    val resort by store.resort.collectAsState()
    val isOnline by store.isOnline.collectAsState()
    val isSyncing by store.isSyncing.collectAsState()
    val statusText by store.connectionStatusText.collectAsState()

    var selectedTab by remember { mutableStateOf(NavigationTab.DASHBOARD) }
    val view = LocalView.current

    Scaffold(
        modifier = Modifier.fillMaxSize(),
        topBar = {
            ResortStatsBar(
                resort = resort,
                isOnline = isOnline,
                isSyncing = isSyncing,
                statusText = statusText,
                modifier = Modifier.statusBarsPadding()
            )
        },
        bottomBar = {
            NavigationBar(
                tonalElevation = 6.dp,
                modifier = Modifier.navigationBarsPadding()
            ) {
                NavigationTab.entries.forEach { tab ->
                    NavigationBarItem(
                        selected = selectedTab == tab,
                        onClick = {
                            view.performHapticFeedback(HapticFeedbackConstants.KEYBOARD_TAP)
                            selectedTab = tab
                        },
                        icon = {
                            Icon(imageVector = tab.icon, contentDescription = tab.label)
                        },
                        label = {
                            Text(tab.label)
                        }
                    )
                }
            }
        }
    ) { innerPadding ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
        ) {
            AnimatedContent(
                targetState = selectedTab,
                transitionSpec = {
                    fadeIn() togetherWith fadeOut()
                },
                label = "ScreenTransition"
            ) { currentTab ->
                when (currentTab) {
                    NavigationTab.DASHBOARD -> DashboardScreen(
                        store = store,
                        onNavigateToMap = { selectedTab = NavigationTab.TRAIL_MAP }
                    )
                    NavigationTab.TRAIL_MAP -> TrailMapScreen(store = store)
                    NavigationTab.VILLAGE -> VillageScreen(store = store)
                    NavigationTab.OPERATIONS -> OperationsScreen(store = store)
                    NavigationTab.ONLINE -> OnlineScreen(store = store)
                }
            }
        }
    }
}
