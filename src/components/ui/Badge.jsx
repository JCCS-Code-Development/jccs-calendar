const COLORS = {
  Scheduled:  'bg-blue-100 text-blue-700',
  Completed:  'bg-green-100 text-green-700',
  Cancelled:  'bg-red-100 text-red-700',
  'In Progress': 'bg-amber-100 text-amber-700',
  High:    'bg-red-100 text-red-700',
  Normal:  'bg-gray-100 text-gray-600',
  Low:     'bg-blue-50 text-blue-500',
  default: 'bg-gray-100 text-gray-600',
}

export default function Badge({ label, className = '' }) {
  const color = COLORS[label] || COLORS.default
  return (
    <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${color} ${className}`}>
      {label}
    </span>
  )
}
