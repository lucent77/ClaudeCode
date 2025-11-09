import React, { useState } from 'react';
import { useApp } from '../contexts/AppContext';
import { Settings, Edit, Save, X } from 'lucide-react';

const MachineManagement = () => {
  const { machines, updateMachine, tools } = useApp();
  const [editingMachine, setEditingMachine] = useState(null);
  const [formData, setFormData] = useState({});

  const handleEdit = (machine) => {
    setEditingMachine(machine.id);
    setFormData({
      runtime: machine.runtime || 0,
      downtime: machine.downtime || 0,
      currentJob: machine.currentJob || '',
      status: machine.status || 'idle'
    });
  };

  const handleSave = () => {
    if (editingMachine) {
      updateMachine(editingMachine, formData);
      setEditingMachine(null);
      setFormData({});
    }
  };

  const handleCancel = () => {
    setEditingMachine(null);
    setFormData({});
  };

  const getMachineTools = (machineId) => {
    return tools.filter(tool => tool.machineId === machineId);
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white shadow-sm border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex items-center gap-3">
            <Settings className="h-6 w-6 text-gray-700" />
            <div>
              <h1 className="text-2xl font-bold text-gray-900">장비 관리</h1>
              <p className="text-sm text-gray-600 mt-1">CNC 장비 현황 및 가동 데이터 관리</p>
            </div>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div className="space-y-4">
          {machines.map(machine => {
            const isEditing = editingMachine === machine.id;
            const machineTools = getMachineTools(machine.id);

            return (
              <div key={machine.id} className="bg-white rounded-lg shadow border border-gray-200">
                <div className="p-6">
                  {/* Header */}
                  <div className="flex items-center justify-between mb-4">
                    <div>
                      <h3 className="text-xl font-bold text-gray-900">{machine.name}</h3>
                      <p className="text-sm text-gray-500">ID: {machine.id}</p>
                    </div>
                    <div>
                      {!isEditing ? (
                        <button
                          onClick={() => handleEdit(machine)}
                          className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2"
                        >
                          <Edit className="h-4 w-4" />
                          편집
                        </button>
                      ) : (
                        <div className="flex gap-2">
                          <button
                            onClick={handleSave}
                            className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center gap-2"
                          >
                            <Save className="h-4 w-4" />
                            저장
                          </button>
                          <button
                            onClick={handleCancel}
                            className="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 flex items-center gap-2"
                          >
                            <X className="h-4 w-4" />
                            취소
                          </button>
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Machine Data */}
                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <div>
                      <label className="block text-xs font-medium text-gray-700 mb-1">상태</label>
                      {isEditing ? (
                        <select
                          value={formData.status}
                          onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                          <option value="running">가동 중</option>
                          <option value="idle">대기</option>
                          <option value="maintenance">정비</option>
                        </select>
                      ) : (
                        <p className="text-sm font-semibold text-gray-900">{machine.status}</p>
                      )}
                    </div>

                    <div>
                      <label className="block text-xs font-medium text-gray-700 mb-1">가동 시간 (h)</label>
                      {isEditing ? (
                        <input
                          type="number"
                          value={formData.runtime}
                          onChange={(e) => setFormData({ ...formData, runtime: parseFloat(e.target.value) })}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                      ) : (
                        <p className="text-sm font-semibold text-gray-900">{machine.runtime || 0}h</p>
                      )}
                    </div>

                    <div>
                      <label className="block text-xs font-medium text-gray-700 mb-1">다운타임 (h)</label>
                      {isEditing ? (
                        <input
                          type="number"
                          value={formData.downtime}
                          onChange={(e) => setFormData({ ...formData, downtime: parseFloat(e.target.value) })}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                      ) : (
                        <p className="text-sm font-semibold text-gray-900">{machine.downtime || 0}h</p>
                      )}
                    </div>

                    <div>
                      <label className="block text-xs font-medium text-gray-700 mb-1">현재 작업</label>
                      {isEditing ? (
                        <input
                          type="text"
                          value={formData.currentJob}
                          onChange={(e) => setFormData({ ...formData, currentJob: e.target.value })}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="작업명 입력"
                        />
                      ) : (
                        <p className="text-sm font-semibold text-gray-900">{machine.currentJob || '작업 없음'}</p>
                      )}
                    </div>
                  </div>

                  {/* Attached Tools */}
                  <div className="pt-4 border-t border-gray-200">
                    <h4 className="text-sm font-semibold text-gray-900 mb-2">장착된 공구 ({machineTools.length}개)</h4>
                    {machineTools.length > 0 ? (
                      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                        {machineTools.map(tool => (
                          <div key={tool.id} className="bg-gray-50 border border-gray-200 rounded p-2">
                            <p className="text-xs font-semibold text-gray-900">{tool.code}</p>
                            <p className="text-xs text-gray-600">{tool.name}</p>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <p className="text-sm text-gray-500">장착된 공구가 없습니다.</p>
                    )}
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
};

export default MachineManagement;
