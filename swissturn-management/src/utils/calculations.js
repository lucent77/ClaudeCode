/**
 * Calculate OEE (Overall Equipment Effectiveness)
 * OEE = Availability × Performance × Quality
 */
export const calculateOEE = (availability, performance, quality) => {
  return (availability * performance * quality) * 100;
};

/**
 * Calculate availability
 * Availability = Operating Time / Planned Production Time
 */
export const calculateAvailability = (operatingTime, plannedTime) => {
  if (plannedTime === 0) return 0;
  return operatingTime / plannedTime;
};

/**
 * Calculate performance
 * Performance = (Total Count / Operating Time) / Ideal Run Rate
 */
export const calculatePerformance = (totalCount, operatingTime, idealRunRate) => {
  if (operatingTime === 0 || idealRunRate === 0) return 0;
  return (totalCount / operatingTime) / idealRunRate;
};

/**
 * Calculate quality
 * Quality = Good Count / Total Count
 */
export const calculateQuality = (goodCount, totalCount) => {
  if (totalCount === 0) return 0;
  return goodCount / totalCount;
};

/**
 * Calculate tool lifespan remaining percentage
 */
export const calculateToolLifeRemaining = (currentUsage, lifespanLimit) => {
  if (lifespanLimit === 0) return 100;
  const remaining = ((lifespanLimit - currentUsage) / lifespanLimit) * 100;
  return Math.max(0, Math.min(100, remaining));
};

/**
 * Get tool status based on remaining life
 */
export const getToolStatus = (remainingPercent) => {
  if (remainingPercent > 50) return 'good';
  if (remainingPercent > 20) return 'warning';
  return 'critical';
};

/**
 * Calculate estimated completion time
 */
export const calculateEstimatedCompletion = (totalUnits, completedUnits, startTime, currentTime) => {
  if (completedUnits === 0) return null;

  const elapsedTime = currentTime - startTime;
  const timePerUnit = elapsedTime / completedUnits;
  const remainingUnits = totalUnits - completedUnits;
  const estimatedRemainingTime = timePerUnit * remainingUnits;

  return new Date(currentTime.getTime() + estimatedRemainingTime);
};

/**
 * Format time duration in hours and minutes
 */
export const formatDuration = (hours) => {
  const h = Math.floor(hours);
  const m = Math.round((hours - h) * 60);
  return `${h}h ${m}m`;
};

/**
 * Calculate achievement percentage
 */
export const calculateAchievement = (actual, target) => {
  if (target === 0) return 0;
  return (actual / target) * 100;
};

/**
 * Parse numeric value safely
 */
export const parseNumber = (value, defaultValue = 0) => {
  const parsed = parseFloat(value);
  return isNaN(parsed) ? defaultValue : parsed;
};

/**
 * Update tool usage based on machine runtime
 * Each tool has a usage ratio that determines how much it's used during machining
 */
export const updateToolUsage = (tool, machineRuntime, usageRatio = 1.0) => {
  const additionalUsage = machineRuntime * usageRatio;
  return {
    ...tool,
    currentUsage: tool.currentUsage + additionalUsage
  };
};
