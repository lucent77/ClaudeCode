import React, { createContext, useContext, useState, useEffect } from 'react';

const AppContext = createContext();

export const useApp = () => {
  const context = useContext(AppContext);
  if (!context) {
    throw new Error('useApp must be used within AppProvider');
  }
  return context;
};

export const AppProvider = ({ children }) => {
  const [user, setUser] = useState({ role: 'admin', name: '관리자' }); // admin or operator
  const [machines, setMachines] = useState([
    {
      id: 'MP1',
      name: 'MP1',
      status: 'running',
      currentJob: null,
      tools: [],
      oee: 0,
      runtime: 0,
      downtime: 0
    },
    {
      id: 'MP2',
      name: 'MP2',
      status: 'idle',
      currentJob: null,
      tools: [],
      oee: 0,
      runtime: 0,
      downtime: 0
    },
    {
      id: 'MP3',
      name: 'MP3',
      status: 'running',
      currentJob: null,
      tools: [],
      oee: 0,
      runtime: 0,
      downtime: 0
    },
    {
      id: 'HW1',
      name: 'HW1',
      status: 'maintenance',
      currentJob: null,
      tools: [],
      oee: 0,
      runtime: 0,
      downtime: 0
    },
    {
      id: 'HW2',
      name: 'HW2',
      status: 'running',
      currentJob: null,
      tools: [],
      oee: 0,
      runtime: 0,
      downtime: 0
    },
    {
      id: 'HW3',
      name: 'HW3',
      status: 'running',
      currentJob: null,
      tools: [],
      oee: 0,
      runtime: 0,
      downtime: 0
    },
    {
      id: 'HW4',
      name: 'HW4',
      status: 'running',
      currentJob: null,
      tools: [],
      oee: 0,
      runtime: 0,
      downtime: 0
    },
    {
      id: 'HW5',
      name: 'HW5',
      status: 'running',
      currentJob: null,
      tools: [],
      oee: 0,
      runtime: 0,
      downtime: 0
    }
  ]);

  const [tools, setTools] = useState([]);
  const [usedTools, setUsedTools] = useState([]);
  const [productionData, setProductionData] = useState([]);
  const [jobs, setJobs] = useState([]);

  // Load initial data from CSV
  useEffect(() => {
    loadInitialData();
  }, []);

  const loadInitialData = async () => {
    // This will be implemented to load CSV data
    // For now, we'll use sample data
    const sampleTools = [
      {
        id: 'D-08',
        code: 'D-08',
        name: '.80 DRILL',
        category: 'TWIST DRILL',
        size: '0.8',
        supplier: 'N/A',
        supplierModel: 'N/A',
        currentStock: 5,
        minStock: 5,
        lifespanType: 'time',
        lifespanLimit: 200,
        currentUsage: 0,
        status: 'available',
        description: 'High-speed steel drill bit for general purpose drilling',
        machineId: null
      },
      {
        id: 'D-11',
        code: 'D-11',
        name: '1.1 DRILL',
        category: 'TWIST DRILL',
        size: '1.1',
        supplier: 'N/A',
        supplierModel: 'N/A',
        currentStock: 5,
        minStock: 5,
        lifespanType: 'time',
        lifespanLimit: 200,
        currentUsage: 0,
        status: 'available',
        description: 'Carbide end mill for precision milling operations',
        machineId: null
      }
    ];
    setTools(sampleTools);
  };

  const updateMachine = (machineId, updates) => {
    setMachines(prev => prev.map(m =>
      m.id === machineId ? { ...m, ...updates } : m
    ));
  };

  const addTool = (tool) => {
    setTools(prev => [...prev, { ...tool, id: `TOOL-${Date.now()}` }]);
  };

  const updateTool = (toolId, updates) => {
    setTools(prev => prev.map(t =>
      t.id === toolId ? { ...t, ...updates } : t
    ));
  };

  const replaceTool = (oldToolId, newTool) => {
    // Move old tool to used tools
    const oldTool = tools.find(t => t.id === oldToolId);
    if (oldTool) {
      setUsedTools(prev => [...prev, {
        ...oldTool,
        replacedAt: new Date().toISOString(),
        finalUsage: oldTool.currentUsage
      }]);
    }

    // Add new tool
    addTool(newTool);

    // Remove old tool from active tools
    setTools(prev => prev.filter(t => t.id !== oldToolId));
  };

  const assignToolToMachine = (toolId, machineId) => {
    updateTool(toolId, { machineId, status: 'in-use' });

    const tool = tools.find(t => t.id === toolId);
    if (tool) {
      updateMachine(machineId, {
        tools: [...(machines.find(m => m.id === machineId)?.tools || []), toolId]
      });
    }
  };

  const removeToolFromMachine = (toolId, machineId) => {
    updateTool(toolId, { machineId: null, status: 'available' });

    updateMachine(machineId, {
      tools: machines.find(m => m.id === machineId)?.tools.filter(t => t !== toolId) || []
    });
  };

  const addProductionRecord = (record) => {
    setProductionData(prev => [...prev, { ...record, id: `PROD-${Date.now()}` }]);
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
    setJobs
  };

  return (
    <AppContext.Provider value={value}>
      {children}
    </AppContext.Provider>
  );
};
