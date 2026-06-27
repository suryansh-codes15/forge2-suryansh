import { useState, useEffect } from 'react';
import { BrowserRouter, Routes, Route, Navigate, NavLink, useNavigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './AuthContext';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import DashboardPage from './pages/DashboardPage';
import TicketsPage from './pages/TicketsPage';
import TicketDetailPage from './pages/TicketDetailPage';
import NewTicketPage from './pages/NewTicketPage';
import './index.css';

function Sidebar({ unreadCount, onToggleNotifications }) {
  const { user, organization, signOut } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    try { const { logout } = await import('./api'); await logout(); } catch {}
    signOut();
    navigate('/login');
  };

  return (
    <aside className="sidebar">
      <div className="sidebar-logo">
        <h1>PulseDesk</h1>
        <span>{organization?.name || 'Help Desk'}</span>
      </div>
      <nav className="sidebar-nav">
        {user?.role !== 'customer' && (
          <NavLink to="/dashboard" className={({isActive}) => `nav-link${isActive?' active':''}`}>
            <span className="nav-icon">📊</span> Dashboard
          </NavLink>
        )}
        <NavLink to="/tickets" className={({isActive}) => `nav-link${isActive?' active':''}`}>
          <span className="nav-icon">🎫</span> {user?.role === 'customer' ? 'My Tickets' : 'Tickets'}
        </NavLink>
        <NavLink to="/tickets/new" className={({isActive}) => `nav-link${isActive?' active':''}`}>
          <span className="nav-icon">✏️</span> New Ticket
        </NavLink>
        <button className="nav-link" onClick={onToggleNotifications} style={{width:'100%', border:0, outline:'none'}}>
          <span className="nav-icon">🔔</span> Notifications
          {unreadCount > 0 && <span className="badge-unread">{unreadCount}</span>}
        </button>
      </nav>
      <div className="sidebar-footer">
        <strong>{user?.name}</strong>
        <span style={{textTransform:'capitalize'}}>{user?.role}</span>
        <button className="btn btn-ghost btn-sm" style={{marginTop:'12px',width:'100%'}} onClick={handleLogout}>
          Sign Out
        </button>
      </div>
    </aside>
  );
}

function ProtectedRoute({ children }) {
  const { user, loading } = useAuth();
  if (loading) return <div className="spinner" />;
  if (!user) return <Navigate to="/login" replace />;
  return children;
}

function AppLayout({ children }) {
  const { user } = useAuth();
  const [showDrawer, setShowDrawer] = useState(false);
  const [notifications, setNotifications] = useState([]);
  const navigate = useNavigate();

  const fetchNotifications = async () => {
    try {
      const { getNotifications } = await import('./api');
      const res = await getNotifications();
      setNotifications(res.data.data || []);
    } catch {}
  };

  useEffect(() => {
    if (user) {
      fetchNotifications();
      const interval = setInterval(fetchNotifications, 15000);
      return () => clearInterval(interval);
    }
  }, [user]);

  const unreadCount = notifications.filter(n => !n.read_at).length;

  const handleMarkAllRead = async () => {
    try {
      const { markAllNotificationsRead } = await import('./api');
      await markAllNotificationsRead();
      fetchNotifications();
    } catch {}
  };

  const handleNotificationClick = async (n) => {
    try {
      const { markNotificationRead } = await import('./api');
      if (!n.read_at) {
        await markNotificationRead(n.id);
        fetchNotifications();
      }
      setShowDrawer(false);
      navigate(`/tickets/${n.ticket_id}`);
    } catch {}
  };

  return (
    <div className="layout">
      <Sidebar unreadCount={unreadCount} onToggleNotifications={() => setShowDrawer(!showDrawer)} />
      <main className="main-content">{children}</main>

      {/* Notifications Drawer */}
      <div className={`notifications-drawer${showDrawer ? ' open' : ''}`}>
        <div className="notifications-header">
          <h3>Notifications</h3>
          <div style={{display:'flex', gap:'8px'}}>
            {unreadCount > 0 && (
              <button className="btn btn-ghost btn-sm" onClick={handleMarkAllRead}>
                Mark all read
              </button>
            )}
            <button className="btn btn-ghost btn-sm" onClick={() => setShowDrawer(false)}>✕</button>
          </div>
        </div>
        <div className="notifications-list">
          {notifications.length === 0 ? (
            <div style={{padding:'20px', textAlign:'center', color:'var(--text-muted)'}}>
              No notifications yet.
            </div>
          ) : (
            notifications.map(n => (
              <div
                key={n.id}
                className={`notification-item${!n.read_at ? ' unread' : ''}`}
                onClick={() => handleNotificationClick(n)}
              >
                <h5>{n.title}</h5>
                <p>{n.message}</p>
                <span>{new Date(n.created_at).toLocaleString()}</span>
              </div>
            ))
          )}
        </div>
      </div>
    </div>
  );
}

function DashboardRoute() {
  const { user } = useAuth();
  if (user?.role === 'customer') {
    return <Navigate to="/tickets" replace />;
  }
  return <AppLayout><DashboardPage /></AppLayout>;
}

export default function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />
          <Route path="/" element={<Navigate to="/dashboard" replace />} />
          <Route path="/dashboard" element={
            <ProtectedRoute>
              <DashboardRoute />
            </ProtectedRoute>
          } />
          <Route path="/tickets" element={
            <ProtectedRoute>
              <AppLayout><TicketsPage /></AppLayout>
            </ProtectedRoute>
          } />
          <Route path="/tickets/new" element={
            <ProtectedRoute>
              <AppLayout><NewTicketPage /></AppLayout>
            </ProtectedRoute>
          } />
          <Route path="/tickets/:id" element={
            <ProtectedRoute>
              <AppLayout><TicketDetailPage /></AppLayout>
            </ProtectedRoute>
          } />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}
