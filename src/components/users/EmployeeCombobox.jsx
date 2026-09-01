import { useState, useRef, useEffect, useMemo } from 'react'

// Type-ahead picker for FieldClock employees. Type any part of a name (or
// space-separated fragments like "jo do") and the list narrows to matches,
// closest first. Keyboard: ↑/↓ to move, Enter to pick, Esc to close.
const MAX_RESULTS = 8

function rank(employee, tokens) {
  const name = (employee.name ?? '').toLowerCase()
  const extra = `${employee.email ?? ''} ${employee.phone ?? ''}`.toLowerCase()
  const hay = `${name} ${extra}`
  // Every token must appear somewhere.
  if (!tokens.every((t) => hay.includes(t))) return null
  const joined = tokens.join(' ')
  if (name === joined) return 0
  if (name.startsWith(joined)) return 1
  if (name.split(/\s+/).some((w) => w.startsWith(tokens[0]))) return 2
  if (name.includes(joined)) return 3
  return 4
}

export default function EmployeeCombobox({
  employees,
  value,
  onSelect,
  label = 'FieldClock Employee',
  required = false,
}) {
  const selected = employees.find((e) => String(e.id) === String(value)) ?? null
  const [query, setQuery] = useState('')
  const [open, setOpen] = useState(false)
  const [active, setActive] = useState(0)
  const wrapRef = useRef(null)
  const blurTimer = useRef(null)

  // Keep the visible text in sync when a selection is made elsewhere / cleared.
  useEffect(() => {
    if (selected) setQuery(selected.name)
    else if (!open) setQuery('')
  }, [selected, open])

  useEffect(() => () => clearTimeout(blurTimer.current), [])

  const results = useMemo(() => {
    const raw = query.trim().toLowerCase()
    const tokens = raw ? raw.split(/\s+/) : []
    const scored = employees
      .map((e) => ({ e, score: tokens.length ? rank(e, tokens) : 5 }))
      .filter((x) => x.score !== null)
      .sort((a, b) => a.score - b.score || (a.e.name ?? '').localeCompare(b.e.name ?? ''))
    return scored.slice(0, MAX_RESULTS).map((x) => x.e)
  }, [employees, query])

  const choose = (emp) => {
    onSelect(emp)
    setQuery(emp.name)
    setOpen(false)
  }

  const clear = () => {
    onSelect(null)
    setQuery('')
    setOpen(true)
  }

  const onKeyDown = (e) => {
    if (!open && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) { setOpen(true); return }
    if (e.key === 'ArrowDown') { e.preventDefault(); setActive((i) => Math.min(i + 1, results.length - 1)) }
    else if (e.key === 'ArrowUp') { e.preventDefault(); setActive((i) => Math.max(i - 1, 0)) }
    else if (e.key === 'Enter') { if (open && results[active]) { e.preventDefault(); choose(results[active]) } }
    else if (e.key === 'Escape') { setOpen(false) }
  }

  return (
    <div className="flex flex-col gap-1" ref={wrapRef}>
      {label && <label className="text-sm font-medium text-gray-700">{label}</label>}

      <div className="relative">
        <input
          type="text"
          role="combobox"
          aria-expanded={open}
          autoComplete="off"
          required={required && !value}
          className="w-full rounded-xl border border-gray-300 px-4 py-3 pr-9 text-base outline-none transition-colors focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          placeholder="Start typing a name…"
          value={query}
          onChange={(e) => { setQuery(e.target.value); setOpen(true); setActive(0); if (value) onSelect(null) }}
          onFocus={() => setOpen(true)}
          onBlur={() => { blurTimer.current = setTimeout(() => setOpen(false), 120) }}
          onKeyDown={onKeyDown}
        />

        {value ? (
          <button
            type="button"
            onClick={clear}
            aria-label="Clear"
            className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 p-1"
          >
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        ) : (
          <svg className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
          </svg>
        )}

        {open && (
          <ul className="absolute z-20 mt-1 w-full max-h-64 overflow-auto rounded-xl border border-gray-200 bg-white shadow-lg py-1">
            {results.length === 0 ? (
              <li className="px-4 py-2.5 text-sm text-gray-400">No matching employees</li>
            ) : (
              results.map((emp, i) => (
                <li key={emp.id}>
                  <button
                    type="button"
                    onMouseDown={(e) => e.preventDefault()}
                    onClick={() => choose(emp)}
                    onMouseEnter={() => setActive(i)}
                    className={`w-full text-left px-4 py-2.5 text-sm transition-colors ${
                      i === active ? 'bg-brand-50 text-brand-700' : 'text-gray-700 hover:bg-gray-50'
                    }`}
                  >
                    <span className="font-medium">{emp.name}</span>
                    {emp.email && <span className="block text-xs text-gray-400">{emp.email}</span>}
                  </button>
                </li>
              ))
            )}
          </ul>
        )}
      </div>
    </div>
  )
}
