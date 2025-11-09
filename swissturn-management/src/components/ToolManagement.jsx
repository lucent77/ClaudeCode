import React, { useState } from 'react';
import { useApp } from '../contexts/AppContext';
import { Wrench, Plus, Search, Filter, RefreshCw, AlertTriangle } from 'lucide-react';
import { calculateToolLifeRemaining, getToolStatus } from '../utils/calculations';

const ToolManagement = () => {
  const { tools, addTool, updateTool, machines, assignToolToMachine, removeToolFromMachine, replaceTool, usedTools } = useApp();
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState('all');
  const [showAddModal, setShowAddModal] = useState(false);
  const [showReplaceModal, setShowReplaceModal] = useState(false);
  const [selectedTool, setSelectedTool] = useState(null);
  const [newToolData, setNewToolData] = useState({
    code: '',
    name: '',
    category: '',
    size: '',
    supplier: '',
    supplierModel: '',
    currentStock: 0,
    minStock: 0,
    lifespanType: 'time',
    lifespanLimit: 200,
    description: ''
  });

  const filteredTools = tools.filter(tool => {
    const matchesSearch = tool.code.toLowerCase().includes(searchTerm.toLowerCase()) ||
      tool.name.toLowerCase().includes(searchTerm.toLowerCase());

    if (filterStatus === 'all') return matchesSearch;

    const remaining = calculateToolLifeRemaining(tool.currentUsage, tool.lifespanLimit);
    const status = getToolStatus(remaining);

    return matchesSearch && status === filterStatus;
  });

  const handleAddTool = () => {
    addTool(newToolData);
    setShowAddModal(false);
    setNewToolData({
      code: '',
      name: '',
      category: '',
      size: '',
      supplier: '',
      supplierModel: '',
      currentStock: 0,
      minStock: 0,
      lifespanType: 'time',
      lifespanLimit: 200,
      description: ''
    });
  };

  const handleReplaceTool = () => {
    if (selectedTool) {
      replaceTool(selectedTool.id, { ...newToolData, currentUsage: 0, status: 'available', machineId: null });
      setShowReplaceModal(false);
      setSelectedTool(null);
      setNewToolData({
        code: '',
        name: '',
        category: '',
        size: '',
        supplier: '',
        supplierModel: '',
        currentStock: 0,
        minStock: 0,
        lifespanType: 'time',
        lifespanLimit: 200,
        description: ''
      });
    }
  };

  const handleAssignToMachine = (toolId, machineId) => {
    assignToolToMachine(toolId, machineId);
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white shadow-sm border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <Wrench className="h-6 w-6 text-gray-700" />
              <div>
                <h1 className="text-2xl font-bold text-gray-900">공구 관리</h1>
                <p className="text-sm text-gray-600 mt-1">공구 목록 및 수명 관리</p>
              </div>
            </div>
            <button
              onClick={() => setShowAddModal(true)}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2"
            >
              <Plus className="h-4 w-4" />
              공구 추가
            </button>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        {/* Search and Filter */}
        <div className="bg-white rounded-lg shadow p-4 mb-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
              <input
                type="text"
                placeholder="공구 코드 또는 이름으로 검색..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
            <div className="relative">
              <Filter className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
              <select
                value={filterStatus}
                onChange={(e) => setFilterStatus(e.target.value)}
                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none"
              >
                <option value="all">모든 상태</option>
                <option value="good">정상</option>
                <option value="warning">주의</option>
                <option value="critical">위험</option>
              </select>
            </div>
          </div>
        </div>

        {/* Tools Table */}
        <div className="bg-white rounded-lg shadow overflow-hidden">
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">공구 코드</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">공구명</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">카테고리</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">사이즈</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">사용 시간</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">남은 수명</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">장비</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">작업</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredTools.map(tool => {
                  const remaining = calculateToolLifeRemaining(tool.currentUsage, tool.lifespanLimit);
                  const status = getToolStatus(remaining);
                  const statusColor = status === 'good' ? 'text-green-600' : status === 'warning' ? 'text-yellow-600' : 'text-red-600';

                  return (
                    <tr key={tool.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{tool.code}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{tool.name}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{tool.category}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{tool.size}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {tool.currentUsage.toFixed(1)}h / {tool.lifespanLimit}h
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex items-center gap-2">
                          <div className="flex-1">
                            <div className="w-full bg-gray-200 rounded-full h-2">
                              <div
                                className={`h-2 rounded-full ${
                                  status === 'good' ? 'bg-green-500' :
                                  status === 'warning' ? 'bg-yellow-500' :
                                  'bg-red-500'
                                }`}
                                style={{ width: `${remaining}%` }}
                              />
                            </div>
                          </div>
                          <span className={`text-sm font-semibold ${statusColor}`}>
                            {remaining.toFixed(0)}%
                          </span>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {tool.machineId || '-'}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm">
                        <button
                          onClick={() => {
                            setSelectedTool(tool);
                            setShowReplaceModal(true);
                          }}
                          className="text-blue-600 hover:text-blue-900 flex items-center gap-1"
                        >
                          <RefreshCw className="h-4 w-4" />
                          교체
                        </button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>

          {filteredTools.length === 0 && (
            <div className="text-center py-12">
              <p className="text-gray-500">검색 결과가 없습니다.</p>
            </div>
          )}
        </div>

        {/* Used Tools Section */}
        {usedTools.length > 0 && (
          <div className="mt-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">사용 완료 공구</h2>
            <div className="bg-white rounded-lg shadow overflow-hidden">
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">공구 코드</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">공구명</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">최종 사용 시간</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">교체 일시</th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {usedTools.map(tool => (
                      <tr key={tool.id} className="hover:bg-gray-50">
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{tool.code}</td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{tool.name}</td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{tool.finalUsage?.toFixed(1)}h</td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                          {tool.replacedAt ? new Date(tool.replacedAt).toLocaleString('ko-KR') : '-'}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Add Tool Modal */}
      {showAddModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-lg max-w-2xl w-full p-6 max-h-screen overflow-y-auto">
            <h2 className="text-xl font-bold text-gray-900 mb-4">새 공구 추가</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">공구 코드</label>
                <input
                  type="text"
                  value={newToolData.code}
                  onChange={(e) => setNewToolData({ ...newToolData, code: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">공구명</label>
                <input
                  type="text"
                  value={newToolData.name}
                  onChange={(e) => setNewToolData({ ...newToolData, name: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">카테고리</label>
                <input
                  type="text"
                  value={newToolData.category}
                  onChange={(e) => setNewToolData({ ...newToolData, category: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">사이즈</label>
                <input
                  type="text"
                  value={newToolData.size}
                  onChange={(e) => setNewToolData({ ...newToolData, size: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">공급업체</label>
                <input
                  type="text"
                  value={newToolData.supplier}
                  onChange={(e) => setNewToolData({ ...newToolData, supplier: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">모델 번호</label>
                <input
                  type="text"
                  value={newToolData.supplierModel}
                  onChange={(e) => setNewToolData({ ...newToolData, supplierModel: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">수명 한도 (시간)</label>
                <input
                  type="number"
                  value={newToolData.lifespanLimit}
                  onChange={(e) => setNewToolData({ ...newToolData, lifespanLimit: parseFloat(e.target.value) })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">재고 수량</label>
                <input
                  type="number"
                  value={newToolData.currentStock}
                  onChange={(e) => setNewToolData({ ...newToolData, currentStock: parseInt(e.target.value) })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <div className="md:col-span-2">
                <label className="block text-sm font-medium text-gray-700 mb-1">설명</label>
                <textarea
                  value={newToolData.description}
                  onChange={(e) => setNewToolData({ ...newToolData, description: e.target.value })}
                  rows={3}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>
            <div className="flex justify-end gap-2 mt-6">
              <button
                onClick={() => setShowAddModal(false)}
                className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
              >
                취소
              </button>
              <button
                onClick={handleAddTool}
                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
              >
                추가
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Replace Tool Modal */}
      {showReplaceModal && selectedTool && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-lg max-w-2xl w-full p-6 max-h-screen overflow-y-auto">
            <div className="flex items-center gap-2 mb-4">
              <AlertTriangle className="h-6 w-6 text-yellow-600" />
              <h2 className="text-xl font-bold text-gray-900">공구 교체</h2>
            </div>
            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
              <p className="text-sm text-gray-900">
                <span className="font-semibold">{selectedTool.code}</span> ({selectedTool.name})를 새 공구로 교체합니다.
              </p>
              <p className="text-xs text-gray-600 mt-1">
                기존 공구는 사용 완료 목록으로 이동됩니다.
              </p>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">공구 코드</label>
                <input
                  type="text"
                  value={newToolData.code}
                  onChange={(e) => setNewToolData({ ...newToolData, code: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder={selectedTool.code}
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">공구명</label>
                <input
                  type="text"
                  value={newToolData.name}
                  onChange={(e) => setNewToolData({ ...newToolData, name: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder={selectedTool.name}
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">수명 한도 (시간)</label>
                <input
                  type="number"
                  value={newToolData.lifespanLimit}
                  onChange={(e) => setNewToolData({ ...newToolData, lifespanLimit: parseFloat(e.target.value) })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder={selectedTool.lifespanLimit.toString()}
                />
              </div>
            </div>
            <div className="flex justify-end gap-2 mt-6">
              <button
                onClick={() => {
                  setShowReplaceModal(false);
                  setSelectedTool(null);
                }}
                className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
              >
                취소
              </button>
              <button
                onClick={handleReplaceTool}
                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
              >
                교체
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ToolManagement;
