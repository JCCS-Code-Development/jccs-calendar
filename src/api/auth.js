import client from './client'

// Resolves a FieldClock-issued JWT to this user's Calendar-specific role.
// Returns 403 if the user hasn't been provisioned for Calendar yet.
export const verify = () => client.get('/auth/verify').then((r) => r.data)
