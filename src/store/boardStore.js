import { create } from 'zustand'
import { persist, createJSONStorage } from 'zustand/middleware'

// Edit Mode state for the Operations Board. Kept in sessionStorage, not
// localStorage: closing the TV's browser tab drops Edit Mode automatically,
// so the board can't be left unlocked indefinitely.
export const useBoardStore = create(
  persist(
    (set) => ({
      pin: null,        // the verified PIN, attached to every write request
      editMode: false,  // whether the edit overlay is open

      enterEditMode: (pin) => set({ pin, editMode: true }),
      exitEditMode: () => set({ pin: null, editMode: false }),
    }),
    {
      name: 'ops-board-edit',
      storage: createJSONStorage(() => sessionStorage),
    }
  )
)
