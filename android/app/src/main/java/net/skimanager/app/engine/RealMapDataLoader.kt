package net.skimanager.app.engine

import android.content.Context
import com.google.gson.Gson
import com.google.gson.JsonObject
import com.google.gson.reflect.TypeToken
import net.skimanager.app.model.RealMapSector
import net.skimanager.app.model.RealMapSegment
import java.io.InputStreamReader

object RealMapDataLoader {
    data class BundledMapData(
        val segments: List<RealMapSegment>,
        val sectors: List<RealMapSector>
    )

    fun loadBundledMapData(context: Context): BundledMapData {
        return try {
            context.assets.open("RealMapData.json").use { stream ->
                InputStreamReader(stream).use { reader ->
                    val gson = Gson()
                    val jsonObject = gson.fromJson(reader, JsonObject::class.java)

                    val segmentListType = object : TypeToken<List<RealMapSegment>>() {}.type
                    val sectorListType = object : TypeToken<List<RealMapSector>>() {}.type

                    val rawSegments: List<RealMapSegment> = if (jsonObject.has("segments")) {
                        gson.fromJson(jsonObject.get("segments"), segmentListType) ?: emptyList()
                    } else emptyList()

                    val rawSectors: List<RealMapSector> = if (jsonObject.has("sectors")) {
                        gson.fromJson(jsonObject.get("sectors"), sectorListType) ?: emptyList()
                    } else emptyList()

                    // Match starting built items from Demo mountain
                    val starterBuiltIds = setOf(60, 62, 63, 64, 66, 67, 69, 70, 71, 72, 73, 74, 75, 76, 77, 83)

                    val processedSegments = rawSegments.map { seg ->
                        val isStarter = starterBuiltIds.contains(seg.id)
                        seg.copy(
                            isBuilt = isStarter,
                            isOpen = isStarter,
                            conditionPercent = if (isStarter) 95 else 100,
                            groomingPercent = if (isStarter) 88 else 0
                        )
                    }

                    BundledMapData(processedSegments, rawSectors)
                }
            }
        } catch (e: Exception) {
            e.printStackTrace()
            BundledMapData(emptyList(), emptyList())
        }
    }
}
