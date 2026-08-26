## MODIFIED Requirements

### Requirement: Completar un entrenamiento
El sistema SHALL permitir a un atleta registrar los resultados de un entrenamiento propio (distancia, tiempo, RPE de 1 a 10, feedback opcional, evidencia fotográfica opcional), marcándolo como `status=completed`, `delivery_status=received` y registrando `completed_at`. El sistema SHALL almacenar la distancia y el tiempo reales con precisión decimal, sin truncarlos a números enteros.

#### Scenario: Completar sin evidencia
- **WHEN** el atleta completa un entrenamiento enviando solo distancia, tiempo y RPE
- **THEN** el sistema guarda los resultados, marca el entrenamiento como completado y no exige ninguna imagen

#### Scenario: Completar con evidencia
- **WHEN** el atleta adjunta una imagen válida al completar el entrenamiento
- **THEN** el sistema guarda la imagen en el almacenamiento de evidencia y registra su ruta en el entrenamiento

#### Scenario: Distancia y tiempo con decimales
- **WHEN** el atleta registra `actual_distance=8.25` y `actual_time=42.5`
- **THEN** el sistema guarda esos valores con sus decimales exactos, sin redondear a un número entero
