import { Routes, Route, Navigate } from 'react-router-dom'
import ProtectedRoute from './router/ProtectedRoute'
import RoleRoute from './router/RoleRoute'
import AppLayout from './components/layout/AppLayout'
import { useAuth } from './hooks/useAuth'

import Login from './pages/auth/Login'
import CalendarView from './pages/CalendarView'
import AllEvents from './pages/AllEvents'
import MyEvents from './pages/MyEvents'
import Todos from './pages/Todos'
import EventForm from './pages/events/EventForm'
import Users from './pages/users/Users'
import UserForm from './pages/users/UserForm'
import JobsHub from './pages/jobs/JobsHub'
import JobForm from './pages/jobs/JobForm'

// Landing page depends on role: office/admin get the company calendar,
// field users (Lead/Crew) only ever work from their own schedule.
function Home() {
  const { canManageEvents } = useAuth()
  return canManageEvents ? <CalendarView /> : <Navigate to="/my-events" replace />
}

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />

      <Route element={<ProtectedRoute />}>
        <Route element={<AppLayout />}>
          {/* All authenticated users */}
          <Route path="/"          element={<Home />} />
          <Route path="/my-events" element={<MyEvents />} />

          {/* Admin + Office */}
          <Route element={<RoleRoute allowedRoles={['Admin', 'Office']} />}>
            <Route path="/jobs"            element={<JobsHub />} />
            <Route path="/production"      element={<JobsHub />} />
            <Route path="/events"          element={<AllEvents />} />
            <Route path="/todos"           element={<Todos />} />
            <Route path="/events/create"   element={<EventForm />} />
            <Route path="/events/:id/edit" element={<EventForm />} />
            <Route path="/jobs/create"     element={<JobForm />} />
            <Route path="/jobs/:id/edit"   element={<JobForm />} />
          </Route>

          {/* Admin only */}
          <Route element={<RoleRoute allowedRoles={['Admin']} />}>
            <Route path="/users"           element={<Users />} />
            <Route path="/users/create"    element={<UserForm />} />
            <Route path="/users/:id/edit"  element={<UserForm />} />
          </Route>
        </Route>
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}
