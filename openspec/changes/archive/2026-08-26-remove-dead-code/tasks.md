## 1. Eliminar métodos sin callers

- [x] 1.1 Eliminar `Team::find($id)` de `models/Team.php` y verificar `php -l models/Team.php`
- [x] 1.2 Eliminar `Workout::getByCoach`, `Workout::markAsReceived` y `Workout::getPlanStatsByCoach` de `models/Workout.php` y verificar `php -l models/Workout.php`

## 2. Verificación

- [ ] 2.1 Re-indexar el proyecto con el MCP `codebase-memory-mcp` y confirmar con `search_graph` — **no ejecutado, el servidor MCP no respondió en este momento (conexión cerrada)**; queda pendiente re-intentar
- [x] 2.2 Grep de control en todo el repo (`Team::find(`, `Workout::getByCoach(`, `Workout::markAsReceived(`, `Workout::getPlanStatsByCoach(`) para confirmar que no queda ningún caller — 0 coincidencias en todo el repositorio
