# Actualización OTA de la app Prestamista

La app Android consulta `https://prestamista.bsolutions.dev/update/latest.json` y, si
`versionCode` es mayor al instalado, ofrece descargar e instalar el APK.

Esta carpeta es `public/update/` del Laravel, así que se sirve directamente por HTTPS
en `https://prestamista.bsolutions.dev/update/...` (los archivos existentes se entregan
sin pasar por el router de Laravel).

## Cómo publicar una actualización nueva

1. En la app Android, en `app/build.gradle.kts`, sube **ambos**: `versionCode` (+1) y
   `versionName` (p. ej. "1.1").
2. Compila el APK **de release firmado con la misma clave de siempre**:
   `./gradlew assembleRelease`. Si cambias la clave de firma, la actualización fallará
   con "aplicación no instalada".
3. Sube al VPS, a esta carpeta (`public/update/`):
   - El APK como `app-release.apk` (súbelo por SFTP/scp; NO lo commitees a git, es pesado).
   - Actualiza `latest.json` con el nuevo `versionCode`/`versionName`/`notes`.

## latest.json

```json
{
  "versionCode": 2,
  "versionName": "1.1",
  "apkUrl": "https://prestamista.bsolutions.dev/update/app-release.apk",
  "notes": "Qué cambió (se muestra al usuario).",
  "mandatory": false
}
```

- `versionCode`: DEBE ser mayor al instalado para que aparezca la actualización.
- `apkUrl`: URL pública HTTPS del APK.
- La URL que consulta la app se define en `BuildConfig.UPDATE_MANIFEST_URL`
  (cambiable sin tocar código con `-PUPDATE_MANIFEST_URL=...` o en `local.properties`).
