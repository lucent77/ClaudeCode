# SwissTurn CNC Parser - Task List

## Status Legend
- [ ] Not started
- [~] In progress
- [x] Complete
- [!] Blocked

---

## Phase 1: Foundation & Parsing

### 1.1 Project Setup
- [x] Initialize project directory structure
- [x] Create database schema file
- [x] Create base PHP routing system
- [x] Build main layout template
- [x] Create database connection utility

### 1.2 Machine Profile System
- [x] Create machines database table
- [x] Create machine_profiles table
- [x] Build Machine model class
- [x] Build MachineProfile loader
- [x] Create MachineController with CRUD
- [x] Build machine list view
- [x] Build machine edit view
- [x] Seed CINCOM L20E-2M10 data
- [x] Seed HANWHA XD20 data
- [x] Seed HANWHA XD26II-V data
- [x] Seed HANWHA XM20 data

### 1.3 G-Code Knowledge Base
- [x] Create g_codes database table
- [x] Build GCode model class
- [x] Build GCodeRepository class
- [x] Create GCodeController with CRUD
- [x] Build G-code list view with filtering
- [x] Build G-code edit form
- [x] Seed universal G-codes
- [x] Seed machine-specific G-codes

### 1.4 M-Code Knowledge Base
- [x] Create m_codes database table
- [x] Build MCode model class
- [x] Build MCodeRepository class
- [x] Create MCodeController with CRUD
- [x] Build M-code list view
- [x] Build M-code edit form
- [x] Seed universal M-codes
- [x] Seed machine-specific M-codes

### 1.5 Parser Engine - Lexer
- [x] Create Token class with types enum
- [x] Build Lexer class
- [x] Implement all token types

### 1.6 Parser Engine - Semantic Parser
- [x] Create ModalState class
- [x] Create IR/Program class
- [x] Create IR/Line class
- [x] Create IR/Command class
- [x] Build Parser class
- [x] Implement modal state tracking

### 1.7 Auto-Commenting Engine
- [x] Build ExplanationGenerator class
- [x] Generate line-by-line explanations

### 1.8 Program Management UI
- [x] Create programs database table
- [x] Create parsed_lines database table
- [x] Build Program model class
- [x] Build ParsedLine model class
- [x] Create ProgramController
- [x] Build program upload form
- [x] Build annotated code viewer

---

## Phase 2: Templates & Process Detection (Future)

### 2.1 Process Detection
- [ ] Define ProcessType enum
- [ ] Create process_blocks table
- [ ] Build ProcessDetector class
- [ ] Implement pattern matchers

### 2.2 Template System
- [ ] Create templates table
- [ ] Build Template model
- [ ] Build TemplateEngine class
- [ ] Create template editor UI

### 2.3 Block Composer
- [ ] Create saved_blocks table
- [ ] Build block library UI
- [ ] Implement drag-drop reordering

---

## Phase 3: DWG Integration (Future)

### 3.1 DWG Viewer
- [ ] Research web DWG libraries
- [ ] Integrate chosen library
- [ ] Build viewer component

### 3.2 Toolpath Visualization
- [ ] Build ToolpathBuilder class
- [ ] Create canvas overlay renderer
