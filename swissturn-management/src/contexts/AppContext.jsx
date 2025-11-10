import React, { createContext, useContext, useState, useEffect } from 'react';
import api from '../services/api';

const AppContext = createContext();

export const useApp = () => {
  const context = useContext(AppContext);
  if (!context) {
    throw new Error('useApp must be used within AppProvider');
  }
  return context;
};

export const AppProvider = ({ children }) => {
  const [user, setUser] = useState({ role: 'admin', name: '관리자' });
  const [machines, setMachines] = useState([]);
  const [tools, setTools] = useState([]);
  const [usedTools, setUsedTools] = useState([]);
  const [productionData, setProductionData] = useState([]);
  const [jobs, setJobs] = useState([]);
  const [loading, setLoading] = useState(true);

  // 초기 데이터 로드
  useEffect(() => {
    loadInitialData();
  }, []);

  const loadInitialData = async () => {
    try {
      setLoading(true);

      // 병렬로 데이터 로드
      const [machinesRes, toolsRes, usedToolsRes] = await Promise.all([
        api.machines.getAll().catch(() => ({ data: [] })),
        api.tools.getAll().catch(() => ({ data: [] })),
        api.tools.getUsed().catch(() => ({ data: [] }))
      ]);

      setMachines(machinesRes.data || []);
      setTools(toolsRes.data || []);
      setUsedTools(usedToolsRes.data || []);
    } catch (error) {
      console.error('Failed to load initial data:', error);
    } finally {
      setLoading(false);
    }
  };

  const updateMachine = async (machineId, updates) => {
    try {
      const res = await api.machines.update(machineId, updates);

      if (res.success) {
        setMachines(prev => prev.map(m =>
          m.id === machineId ? { ...m, ...updates } : m
        ));
      }
    } catch (error) {
      console.error('Failed to update machine:', error);
      throw error;
    }
  };

  const addTool = async (tool) => {
    try {
      const res = await api.tools.create(tool);

      if (res.success) {
        // 새로 생성된 공구 다시 로드
        const toolRes = await api.tools.getOne(res.data.id);
        setTools(prev => [...prev, toolRes.data]);
      }
    } catch (error) {
      console.error('Failed to add tool:', error);
      throw error;
    }
  };

  const updateTool = async (toolId, updates) => {
    try {
      const res = await api.tools.update(toolId, updates);

      if (res.success) {
        setTools(prev => prev.map(t =>
          t.id === toolId ? { ...t, ...updates } : t
        ));
      }
    } catch (error) {
      console.error('Failed to update tool:', error);
      throw error;
    }
  };

  const replaceTool = async (oldToolId, newTool) => {
    try {
      const res = await api.tools.replace(oldToolId, newTool);

      if (res.success) {
        // 데이터 다시 로드
        const [toolsRes, usedToolsRes] = await Promise.all([
          api.tools.getAll(),
          api.tools.getUsed()
        ]);

        setTools(toolsRes.data);
        setUsedTools(usedToolsRes.data);
      }
    } catch (error) {
      console.error('Failed to replace tool:', error);
      throw error;
    }
  };

  const assignToolToMachine = async (toolId, machineId) => {
    try {
      await updateTool(toolId, { machineId, status: 'in-use' });
    } catch (error) {
      console.error('Failed to assign tool to machine:', error);
      throw error;
    }
  };

  const removeToolFromMachine = async (toolId, machineId) => {
    try {
      await updateTool(toolId, { machineId: null, status: 'available' });
    } catch (error) {
      console.error('Failed to remove tool from machine:', error);
      throw error;
    }
  };

  const addProductionRecord = async (record) => {
    try {
      const res = await api.production.create(record);

      if (res.success) {
        setProductionData(prev => [...prev, { ...record, id: res.data.id }]);
      }
    } catch (error) {
      console.error('Failed to add production record:', error);
      throw error;
    }
  };

  const value = {
    user,
    setUser,
    machines,
    updateMachine,
    tools,
    addTool,
    updateTool,
    replaceTool,
    assignToolToMachine,
    removeToolFromMachine,
    usedTools,
    productionData,
    addProductionRecord,
    jobs,
    setJobs,
    loading,
    refreshData: loadInitialData
  };

  return (
    <AppContext.Provider value={value}>
      {children}
    </AppContext.Provider>
  );
};
