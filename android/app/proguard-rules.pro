# Ski Manager Proguard Rules
-keepattributes *Annotation*
-keepclassmembers class * {
    @com.google.gson.annotations.SerializedName <fields>;
}
-keep class net.skimanager.app.model.** { *; }
-keep class net.skimanager.app.network.** { *; }
