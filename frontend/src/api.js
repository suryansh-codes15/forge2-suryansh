import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: { 
    'Content-Type': 'application/json', 
    'Accept': 'application/json',
    'Bypass-Tunnel-Reminder': 'true'
  },
});

// Inject token on every request
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

// Auth
export const register = (data) => api.post('/auth/register', data);
export const login = (data) => api.post('/auth/login', data);
export const logout = () => api.post('/auth/logout');
export const me = () => api.get('/auth/me');

// Tickets
export const getTickets = (params) => api.get('/tickets', { params });
export const getTicket = (id) => api.get(`/tickets/${id}`);
export const createTicket = (data) => api.post('/tickets', data);
export const updateTicket = (id, data) => api.patch(`/tickets/${id}`, data);
export const deleteTicket = (id) => api.delete(`/tickets/${id}`);

// Comments
export const getComments = (ticketId) => api.get(`/tickets/${ticketId}/comments`);
export const createComment = (ticketId, data) => api.post(`/tickets/${ticketId}/comments`, data);

// Dashboard
export const getDashboardStats = () => api.get('/dashboard/stats');
export const getAgents = () => api.get('/dashboard/agents');
export const getCustomers = () => api.get('/dashboard/customers');

// Notifications
export const getNotifications = () => api.get('/notifications');
export const markNotificationRead = (id) => api.post(`/notifications/${id}/read`);
export const markAllNotificationsRead = () => api.post('/notifications/read-all');

export default api;
