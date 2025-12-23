-- SwissTurn CNC Parser - Machine Seeds
-- Supported SwissTurn machine configurations

INSERT INTO machine (name, manufacturer, controller_type, description, is_active) VALUES
('L20E-2M10', 'CINCOM', 'FANUC 32i-B', 'CINCOM L20E Type VIII 2M10 - 20mm bar capacity Swiss-type lathe with gang tooling and 8-station turret', 1),
('XD20', 'HANWHA', 'FANUC 32i-B', 'HANWHA XD20 - 20mm bar capacity Swiss-type lathe with 12-station turret', 1),
('XD26II-V', 'HANWHA', 'FANUC 32i-B', 'HANWHA XD26II-V - 26mm bar capacity Swiss-type lathe with Y-axis and sub-spindle', 1),
('XM20', 'HANWHA', 'FANUC 31i', 'HANWHA XM20 - Entry-level 20mm Swiss-type lathe with 8-station turret', 1);

-- Modal Groups Reference
INSERT INTO modal_group (id, name, description) VALUES
(0, 'Non-modal', 'Non-modal codes that execute once'),
(1, 'Motion', 'Motion commands (G00, G01, G02, G03)'),
(2, 'Plane Selection', 'Work plane selection (G17, G18, G19)'),
(3, 'Distance Mode', 'Absolute/Incremental mode (G90, G91)'),
(5, 'Feed Mode', 'Feed rate mode (G94, G95)'),
(6, 'Units', 'Unit selection (G20, G21)'),
(7, 'Cutter Compensation', 'Tool nose compensation (G40, G41, G42)'),
(8, 'Tool Length Offset', 'Tool length compensation (G43, G49)'),
(9, 'Canned Cycle Return', 'Canned cycle return level (G98, G99)'),
(10, 'Canned Cycles', 'Drilling and boring cycles (G80-G89)'),
(12, 'Work Offset', 'Work coordinate system (G54-G59)'),
(13, 'Exact Stop', 'Exact stop mode (G61, G64)');
