-- SwissTurn CNC Parser - Universal M-Code Seeds
-- These M-codes apply to all supported machines

-- Program Control
INSERT INTO m_code (machine_id, code, name, description, semantic_type, explanation_template, is_active) VALUES
(NULL, 'M00', 'Program Stop', 'Unconditional program stop. Operator must press cycle start to continue.', 'PROGRAM_CONTROL', 'Program STOP', 1),
(NULL, 'M01', 'Optional Stop', 'Optional program stop. Only stops if optional stop switch is ON.', 'PROGRAM_CONTROL', 'Optional STOP', 1),
(NULL, 'M02', 'Program End', 'End of program. Resets to program start.', 'PROGRAM_CONTROL', 'Program END', 1),
(NULL, 'M30', 'Program End & Rewind', 'End program and rewind to beginning.', 'PROGRAM_CONTROL', 'Program END and rewind', 1),
(NULL, 'M98', 'Subprogram Call', 'Call a subprogram', 'PROGRAM_CONTROL', 'Call subprogram', 1),
(NULL, 'M99', 'Subprogram Return', 'Return from subprogram or end of main program loop', 'PROGRAM_CONTROL', 'Return from subprogram', 1);

-- Spindle Control
INSERT INTO m_code (machine_id, code, name, description, semantic_type, explanation_template, is_active) VALUES
(NULL, 'M03', 'Spindle ON CW', 'Start main spindle rotation clockwise (forward)', 'SPINDLE_CONTROL', 'Spindle ON clockwise', 1),
(NULL, 'M04', 'Spindle ON CCW', 'Start main spindle rotation counter-clockwise (reverse)', 'SPINDLE_CONTROL', 'Spindle ON counter-clockwise', 1),
(NULL, 'M05', 'Spindle Stop', 'Stop all spindle rotation', 'SPINDLE_CONTROL', 'Spindle STOP', 1);

-- Coolant Control
INSERT INTO m_code (machine_id, code, name, description, semantic_type, explanation_template, is_active) VALUES
(NULL, 'M07', 'Mist Coolant ON', 'Turn on mist coolant', 'COOLANT_CONTROL', 'Mist coolant ON', 1),
(NULL, 'M08', 'Flood Coolant ON', 'Turn on flood coolant', 'COOLANT_CONTROL', 'Flood coolant ON', 1),
(NULL, 'M09', 'Coolant OFF', 'Turn off all coolant', 'COOLANT_CONTROL', 'Coolant OFF', 1);

-- Tool Change
INSERT INTO m_code (machine_id, code, name, description, semantic_type, explanation_template, is_active) VALUES
(NULL, 'M06', 'Tool Change', 'Execute tool change (used on some machines)', 'TOOL_CHANGE', 'Tool change', 1);

-- Chuck/Collet Control (common to most Swiss-type)
INSERT INTO m_code (machine_id, code, name, description, semantic_type, explanation_template, is_active) VALUES
(NULL, 'M10', 'Chuck Clamp', 'Close/clamp main chuck', 'PROGRAM_CONTROL', 'Chuck clamp', 1),
(NULL, 'M11', 'Chuck Unclamp', 'Open/unclamp main chuck', 'PROGRAM_CONTROL', 'Chuck unclamp', 1);

-- Bar Feeder (common codes)
INSERT INTO m_code (machine_id, code, name, description, semantic_type, explanation_template, is_active) VALUES
(NULL, 'M21', 'Bar Feed Forward', 'Advance bar stock', 'PROGRAM_CONTROL', 'Bar feed forward', 1),
(NULL, 'M22', 'Bar Feed Stop', 'Stop bar feed advance', 'PROGRAM_CONTROL', 'Bar feed stop', 1);

-- Parts Catcher
INSERT INTO m_code (machine_id, code, name, description, semantic_type, explanation_template, is_active) VALUES
(NULL, 'M24', 'Parts Catcher Extend', 'Extend parts catcher/unloader', 'PROGRAM_CONTROL', 'Parts catcher extend', 1),
(NULL, 'M25', 'Parts Catcher Retract', 'Retract parts catcher/unloader', 'PROGRAM_CONTROL', 'Parts catcher retract', 1);

-- Guide Bush Control
INSERT INTO m_code (machine_id, code, name, description, semantic_type, explanation_template, is_active) VALUES
(NULL, 'M68', 'Guide Bush Close', 'Close guide bush collet', 'PROGRAM_CONTROL', 'Guide bush close', 1),
(NULL, 'M69', 'Guide Bush Open', 'Open guide bush collet', 'PROGRAM_CONTROL', 'Guide bush open', 1);

-- Sub Spindle Control
INSERT INTO m_code (machine_id, code, name, description, semantic_type, explanation_template, is_active) VALUES
(NULL, 'M13', 'Sub Spindle ON CW', 'Start sub spindle clockwise', 'SPINDLE_CONTROL', 'Sub spindle ON CW', 1),
(NULL, 'M14', 'Sub Spindle ON CCW', 'Start sub spindle counter-clockwise', 'SPINDLE_CONTROL', 'Sub spindle ON CCW', 1),
(NULL, 'M15', 'Sub Spindle Stop', 'Stop sub spindle', 'SPINDLE_CONTROL', 'Sub spindle STOP', 1);

-- Live Tooling
INSERT INTO m_code (machine_id, code, name, description, semantic_type, explanation_template, is_active) VALUES
(NULL, 'M83', 'Live Tool CW', 'Start live tool rotation clockwise', 'SPINDLE_CONTROL', 'Live tool ON CW', 1),
(NULL, 'M84', 'Live Tool CCW', 'Start live tool rotation counter-clockwise', 'SPINDLE_CONTROL', 'Live tool ON CCW', 1),
(NULL, 'M85', 'Live Tool Stop', 'Stop live tool rotation', 'SPINDLE_CONTROL', 'Live tool STOP', 1);
