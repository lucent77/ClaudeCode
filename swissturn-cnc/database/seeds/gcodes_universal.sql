-- SwissTurn CNC Parser - Universal G-Code Seeds
-- These G-codes apply to all supported machines

-- Motion Codes (Group 1)
INSERT INTO g_code (machine_id, code, name, description, modal_group_id, is_modal, semantic_type, explanation_template, is_active) VALUES
(NULL, 'G00', 'Rapid Positioning', 'Rapid traverse to specified coordinates at maximum speed', 1, 1, 'MOTION', 'Rapid move to {X} {Z} {Y}', 1),
(NULL, 'G01', 'Linear Interpolation', 'Linear movement at programmed feed rate', 1, 1, 'MOTION', 'Linear cut to {X} {Z} at F{F}', 1),
(NULL, 'G02', 'Circular Interpolation CW', 'Clockwise arc movement', 1, 1, 'MOTION', 'Arc CW to {X} {Z} R{R}', 1),
(NULL, 'G03', 'Circular Interpolation CCW', 'Counter-clockwise arc movement', 1, 1, 'MOTION', 'Arc CCW to {X} {Z} R{R}', 1),
(NULL, 'G04', 'Dwell', 'Pause for specified time (P in milliseconds or X in seconds)', 0, 0, 'PROGRAM_CONTROL', 'Dwell for {P} ms', 1);

-- Plane Selection (Group 2)
INSERT INTO g_code (machine_id, code, name, description, modal_group_id, is_modal, semantic_type, explanation_template, is_active) VALUES
(NULL, 'G17', 'XY Plane Selection', 'Select XY plane for arc interpolation', 2, 1, 'COORDINATE_SYSTEM', 'Select XY plane', 1),
(NULL, 'G18', 'ZX Plane Selection', 'Select ZX plane for arc interpolation (default for turning)', 2, 1, 'COORDINATE_SYSTEM', 'Select ZX plane', 1),
(NULL, 'G19', 'YZ Plane Selection', 'Select YZ plane for arc interpolation', 2, 1, 'COORDINATE_SYSTEM', 'Select YZ plane', 1);

-- Distance Mode (Group 3)
INSERT INTO g_code (machine_id, code, name, description, modal_group_id, is_modal, semantic_type, explanation_template, is_active) VALUES
(NULL, 'G90', 'Absolute Positioning', 'Coordinates are absolute from work origin', 3, 1, 'COORDINATE_SYSTEM', 'Absolute positioning mode', 1),
(NULL, 'G91', 'Incremental Positioning', 'Coordinates are incremental from current position', 3, 1, 'COORDINATE_SYSTEM', 'Incremental positioning mode', 1);

-- Feed Mode (Group 5)
INSERT INTO g_code (machine_id, code, name, description, modal_group_id, is_modal, semantic_type, explanation_template, is_active) VALUES
(NULL, 'G94', 'Feed Per Minute', 'Feed rate in units per minute', 5, 1, 'FEED_SPEED', 'Feed per minute mode', 1),
(NULL, 'G95', 'Feed Per Revolution', 'Feed rate in units per revolution', 5, 1, 'FEED_SPEED', 'Feed per revolution mode', 1);

-- Units (Group 6)
INSERT INTO g_code (machine_id, code, name, description, modal_group_id, is_modal, semantic_type, explanation_template, is_active) VALUES
(NULL, 'G20', 'Inch Units', 'Program in inches', 6, 1, 'COORDINATE_SYSTEM', 'Inch mode', 1),
(NULL, 'G21', 'Metric Units', 'Program in millimeters', 6, 1, 'COORDINATE_SYSTEM', 'Metric mode (mm)', 1);

-- Cutter Compensation (Group 7)
INSERT INTO g_code (machine_id, code, name, description, modal_group_id, is_modal, semantic_type, explanation_template, is_active) VALUES
(NULL, 'G40', 'Cancel Cutter Compensation', 'Cancel tool nose radius compensation', 7, 1, 'COMPENSATION', 'Cancel cutter compensation', 1),
(NULL, 'G41', 'Cutter Compensation Left', 'Tool nose radius compensation left', 7, 1, 'COMPENSATION', 'Cutter comp left', 1),
(NULL, 'G42', 'Cutter Compensation Right', 'Tool nose radius compensation right', 7, 1, 'COMPENSATION', 'Cutter comp right', 1);

-- Reference Point
INSERT INTO g_code (machine_id, code, name, description, modal_group_id, is_modal, semantic_type, explanation_template, is_active) VALUES
(NULL, 'G28', 'Return to Reference', 'Return to machine reference point via intermediate point', 0, 0, 'MOTION', 'Return to reference point', 1),
(NULL, 'G30', 'Return to 2nd Reference', 'Return to second reference point', 0, 0, 'MOTION', 'Return to 2nd reference point', 1);

-- Work Offset (Group 12)
INSERT INTO g_code (machine_id, code, name, description, modal_group_id, is_modal, semantic_type, explanation_template, is_active) VALUES
(NULL, 'G54', 'Work Offset 1', 'Select work coordinate system 1', 12, 1, 'COORDINATE_SYSTEM', 'Work offset 1', 1),
(NULL, 'G55', 'Work Offset 2', 'Select work coordinate system 2', 12, 1, 'COORDINATE_SYSTEM', 'Work offset 2', 1),
(NULL, 'G56', 'Work Offset 3', 'Select work coordinate system 3', 12, 1, 'COORDINATE_SYSTEM', 'Work offset 3', 1),
(NULL, 'G57', 'Work Offset 4', 'Select work coordinate system 4', 12, 1, 'COORDINATE_SYSTEM', 'Work offset 4', 1),
(NULL, 'G58', 'Work Offset 5', 'Select work coordinate system 5', 12, 1, 'COORDINATE_SYSTEM', 'Work offset 5', 1),
(NULL, 'G59', 'Work Offset 6', 'Select work coordinate system 6', 12, 1, 'COORDINATE_SYSTEM', 'Work offset 6', 1);

-- Turning Canned Cycles
INSERT INTO g_code (machine_id, code, name, description, modal_group_id, is_modal, semantic_type, explanation_template, is_active) VALUES
(NULL, 'G70', 'Finishing Cycle', 'Finish machining cycle following G71/G72 rough path', 0, 0, 'CANNED_CYCLE', 'Finishing cycle', 1),
(NULL, 'G71', 'Rough Turning Cycle', 'Stock removal in turning (longitudinal)', 0, 0, 'CANNED_CYCLE', 'Rough turning cycle', 1),
(NULL, 'G72', 'Rough Facing Cycle', 'Stock removal in facing', 0, 0, 'CANNED_CYCLE', 'Rough facing cycle', 1),
(NULL, 'G73', 'Pattern Repeat Cycle', 'Pattern repeating for contour machining', 0, 0, 'CANNED_CYCLE', 'Pattern repeat cycle', 1),
(NULL, 'G74', 'Peck Drilling Cycle Z', 'End face peck drilling', 0, 0, 'CANNED_CYCLE', 'Peck drilling (Z-axis)', 1),
(NULL, 'G75', 'Grooving Cycle X', 'Grooving/recessing in X direction', 0, 0, 'CANNED_CYCLE', 'Grooving cycle (X-axis)', 1),
(NULL, 'G76', 'Threading Cycle', 'Thread cutting cycle', 0, 0, 'CANNED_CYCLE', 'Threading cycle', 1);

-- Drilling Canned Cycles
INSERT INTO g_code (machine_id, code, name, description, modal_group_id, is_modal, semantic_type, explanation_template, is_active) VALUES
(NULL, 'G80', 'Cancel Canned Cycle', 'Cancel any active canned cycle', 10, 1, 'CANNED_CYCLE', 'Cancel canned cycle', 1),
(NULL, 'G81', 'Drilling Cycle', 'Simple drilling cycle', 10, 1, 'CANNED_CYCLE', 'Drilling to Z{Z}', 1),
(NULL, 'G82', 'Drill with Dwell', 'Drilling cycle with dwell at bottom', 10, 1, 'CANNED_CYCLE', 'Drilling with dwell to Z{Z}', 1),
(NULL, 'G83', 'Peck Drilling', 'Deep hole peck drilling cycle', 10, 1, 'CANNED_CYCLE', 'Peck drilling to Z{Z}', 1),
(NULL, 'G84', 'Tapping Cycle', 'Rigid tapping cycle', 10, 1, 'CANNED_CYCLE', 'Tapping to Z{Z}', 1),
(NULL, 'G85', 'Boring Cycle', 'Boring cycle - feed out', 10, 1, 'CANNED_CYCLE', 'Boring to Z{Z}', 1),
(NULL, 'G86', 'Boring Cycle Stop', 'Boring cycle - spindle stop, rapid out', 10, 1, 'CANNED_CYCLE', 'Boring with stop to Z{Z}', 1);

-- Canned Cycle Return Level (Group 9)
INSERT INTO g_code (machine_id, code, name, description, modal_group_id, is_modal, semantic_type, explanation_template, is_active) VALUES
(NULL, 'G98', 'Return to Initial Level', 'Return to initial point level in canned cycle', 9, 1, 'CANNED_CYCLE', 'Return to initial level', 1),
(NULL, 'G99', 'Return to R Level', 'Return to R point level in canned cycle', 9, 1, 'CANNED_CYCLE', 'Return to R level', 1);

-- Thread Cutting
INSERT INTO g_code (machine_id, code, name, description, modal_group_id, is_modal, semantic_type, explanation_template, is_active) VALUES
(NULL, 'G32', 'Thread Cutting', 'Single pass thread cutting', 1, 1, 'MOTION', 'Thread cutting', 1),
(NULL, 'G92', 'Thread Cutting Cycle', 'Simple thread cutting cycle (older method)', 0, 0, 'CANNED_CYCLE', 'Thread cutting cycle', 1);

-- Exact Stop (Group 13)
INSERT INTO g_code (machine_id, code, name, description, modal_group_id, is_modal, semantic_type, explanation_template, is_active) VALUES
(NULL, 'G61', 'Exact Stop Mode', 'Machine decelerates to exact stop at each block end', 13, 1, 'MOTION', 'Exact stop mode ON', 1),
(NULL, 'G64', 'Cutting Mode', 'Normal cutting mode with continuous path', 13, 1, 'MOTION', 'Continuous path mode', 1);

-- Coordinate System
INSERT INTO g_code (machine_id, code, name, description, modal_group_id, is_modal, semantic_type, explanation_template, is_active) VALUES
(NULL, 'G50', 'Coordinate System Setting', 'Set workpiece coordinate system / Max spindle speed', 0, 0, 'COORDINATE_SYSTEM', 'Set coordinate system', 1),
(NULL, 'G52', 'Local Coordinate System', 'Set local coordinate system offset', 0, 0, 'COORDINATE_SYSTEM', 'Local coordinate offset', 1),
(NULL, 'G53', 'Machine Coordinate System', 'Move in machine coordinate system (non-modal)', 0, 0, 'COORDINATE_SYSTEM', 'Machine coordinate move', 1);

-- CSS (Constant Surface Speed)
INSERT INTO g_code (machine_id, code, name, description, modal_group_id, is_modal, semantic_type, explanation_template, is_active) VALUES
(NULL, 'G96', 'Constant Surface Speed ON', 'Enable constant surface speed (m/min)', 0, 1, 'FEED_SPEED', 'CSS ON at S{S} m/min', 1),
(NULL, 'G97', 'Constant Surface Speed OFF', 'Disable CSS, use direct RPM', 0, 1, 'FEED_SPEED', 'CSS OFF, RPM mode', 1);
