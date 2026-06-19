* Page background        → bg-base-200
* Header                 → bg-base-100  border-b border-base-300
* Controls card          → bg-base-100  shadow-sm  border border-base-300
* Filter card       → bg-base-100  shadow-sm  border border-base-300
* Table                  → bg-base-100  shadow-sm  border border-base-300
* Table header row       → bg-base-200  (just one step darker, not blue)
* Alternate table rows   → bg-base-200/50  (half-opacity so it's very subtle)
* Warning strip          → bg-warning/10  border-t border-warning/30

## To remove DaisyUI classes

    rg -i '\b(class|:class)\s*=\s*"[^"]*\b(btn|card|modal|drawer|navbar|alert|badge|collapse|join|hero|avatar|steps|timeline|breadcrumbs|carousel|chat|divider|footer|indicator|input|kbd|label|link|loading|mask|menu|progress|radio|range|rating|select|skeleton|stack|stat|status|swap|tab|table|textarea|toast|toggle|tooltip)\b[^"]*"' --type vue
