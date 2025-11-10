/**
 * API 서비스
 * 백엔드 PHP API와 통신
 */

// API 기본 URL (배포시 실제 도메인으로 변경)
const API_BASE_URL = process.env.NODE_ENV === 'production'
  ? '/api'  // 프로덕션: 같은 도메인의 /api
  : 'http://localhost/swissturn-management/api';  // 개발: PHP 서버

/**
 * API 요청 헬퍼
 */
const apiRequest = async (endpoint, options = {}) => {
  const url = `${API_BASE_URL}/${endpoint}`;

  const config = {
    headers: {
      'Content-Type': 'application/json',
      ...options.headers,
    },
    ...options,
  };

  try {
    const response = await fetch(url, config);
    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || 'API request failed');
    }

    return data;
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
};

/**
 * Machines API
 */
export const machinesAPI = {
  getAll: () => apiRequest('machines'),

  getOne: (id) => apiRequest(`machines/${id}`),

  update: (id, data) => apiRequest(`machines/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  }),

  getTools: (id) => apiRequest(`machines/${id}/tools`),
};

/**
 * Tools API
 */
export const toolsAPI = {
  getAll: () => apiRequest('tools'),

  getOne: (id) => apiRequest(`tools/${id}`),

  create: (data) => apiRequest('tools', {
    method: 'POST',
    body: JSON.stringify(data),
  }),

  update: (id, data) => apiRequest(`tools/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  }),

  delete: (id) => apiRequest(`tools/${id}`, {
    method: 'DELETE',
  }),

  replace: (id, data) => apiRequest(`tools/${id}/replace`, {
    method: 'POST',
    body: JSON.stringify(data),
  }),

  getUsed: () => apiRequest('tools/used'),
};

/**
 * Production API
 */
export const productionAPI = {
  getAll: (limit = 100, offset = 0) =>
    apiRequest(`production?limit=${limit}&offset=${offset}`),

  create: (data) => apiRequest('production', {
    method: 'POST',
    body: JSON.stringify(data),
  }),

  bulkInsert: (records) => apiRequest('production', {
    method: 'POST',
    body: JSON.stringify({ bulk: true, records }),
  }),
};

/**
 * Auth API
 */
export const authAPI = {
  login: (username, password) => apiRequest('auth', {
    method: 'POST',
    body: JSON.stringify({ username, password }),
  }),
};

export default {
  machines: machinesAPI,
  tools: toolsAPI,
  production: productionAPI,
  auth: authAPI,
};
