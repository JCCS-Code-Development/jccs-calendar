import { useState } from 'react'
import Modal from '../../../components/ui/Modal'
import Input from '../../../components/ui/Input'
import Button from '../../../components/ui/Button'
import { verifyPin } from '../../../api/board'
import { useBoardT } from '../t'

export default function PinGate({ onClose, onUnlocked }) {
  const T = useBoardT()
  const [pin, setPin] = useState('')
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  const submit = async (e) => {
    e.preventDefault()
    if (!pin.trim()) { setError(T.pinEmpty); return }
    setBusy(true)
    setError('')
    try {
      await verifyPin(pin.trim())
      onUnlocked(pin.trim())
    } catch {
      setError(T.pinWrong)
      setBusy(false)
    }
  }

  return (
    <Modal isOpen onClose={onClose} title={T.pinTitle} size="sm">
      <form onSubmit={submit} className="flex flex-col gap-4">
        <p className="text-sm text-gray-600">{T.pinBody}</p>
        <Input
          label={T.pinLabel}
          type="password"
          inputMode="numeric"
          autoComplete="off"
          autoFocus
          value={pin}
          onChange={(e) => setPin(e.target.value)}
          error={error}
        />
        <div className="flex justify-end gap-2">
          <Button variant="secondary" onClick={onClose} type="button">{T.cancel}</Button>
          <Button type="submit" loading={busy}>{T.unlock}</Button>
        </div>
      </form>
    </Modal>
  )
}
