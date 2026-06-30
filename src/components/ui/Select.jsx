export default function Select({ label, error, className = '', children, ...props }) {
  return (
    <div className={`flex flex-col gap-1 ${className}`}>
      {label && <label className="text-sm font-medium text-gray-700">{label}</label>}
      <select
        className={`w-full rounded-xl border px-4 py-3 text-base outline-none transition-colors bg-white ${error ? 'border-red-400 focus:border-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-100'}`}
        {...props}
      >
        {children}
      </select>
      {error && <p className="text-xs text-red-500">{error}</p>}
    </div>
  )
}
