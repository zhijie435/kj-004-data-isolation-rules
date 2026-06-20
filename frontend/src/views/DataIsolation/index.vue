<template>
  <div class="data-isolation-page">
    <div class="page-header">
      <div class="header-left">
        <h2 class="page-title">数据隔离规则</h2>
        <p class="page-desc">配置数据隔离规则，实现多租户、角色级别的数据访问控制</p>
      </div>
      <div class="header-right">
        <el-button type="primary" @click="handleAdd">
          <el-icon><Plus /></el-icon>新建规则
        </el-button>
      </div>
    </div>

    <el-row :gutter="20" class="stats-cards">
      <el-col :span="6">
        <div class="stat-card">
          <div class="stat-icon primary">
            <el-icon><Lock /></el-icon>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.total }}</div>
            <div class="stat-label">规则总数</div>
          </div>
        </div>
      </el-col>
      <el-col :span="6">
        <div class="stat-card">
          <div class="stat-icon success">
            <el-icon><CircleCheck /></el-icon>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.enabled }}</div>
            <div class="stat-label">已启用</div>
          </div>
        </div>
      </el-col>
      <el-col :span="6">
        <div class="stat-card">
          <div class="stat-icon warning">
            <el-icon><Timer /></el-icon>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.disabled }}</div>
            <div class="stat-label">已禁用</div>
          </div>
        </div>
      </el-col>
      <el-col :span="6">
        <div class="stat-card">
          <div class="stat-icon info">
            <el-icon><DataLine /></el-icon>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.modelCount }}</div>
            <div class="stat-label">覆盖模型</div>
          </div>
        </div>
      </el-col>
    </el-row>

    <el-card class="filter-card">
      <el-form :model="queryParams" inline>
        <el-form-item label="关键词">
          <el-input
            v-model="queryParams.keyword"
            placeholder="搜索规则名称/编码"
            clearable
            style="width: 240px"
            @keyup.enter="handleSearch"
          />
        </el-form-item>
        <el-form-item label="规则类型">
          <el-select v-model="queryParams.rule_type" placeholder="全部" clearable style="width: 140px">
            <el-option label="租户隔离" value="tenant" />
            <el-option label="角色隔离" value="role" />
            <el-option label="自定义" value="custom" />
            <el-option label="字段隔离" value="field" />
          </el-select>
        </el-form-item>
        <el-form-item label="目标模型">
          <el-select v-model="queryParams.model_class" placeholder="全部" clearable style="width: 200px">
            <el-option
              v-for="item in modelClasses"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="queryParams.is_enabled" placeholder="全部" clearable style="width: 120px">
            <el-option label="启用" :value="true" />
            <el-option label="禁用" :value="false" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">
            <el-icon><Search /></el-icon>搜索
          </el-button>
          <el-button @click="handleReset">
            <el-icon><Refresh /></el-icon>重置
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card class="table-card">
      <el-table
        :data="tableData"
        v-loading="loading"
        stripe
        border
        style="width: 100%"
      >
        <el-table-column type="index" label="序号" width="60" align="center" />
        <el-table-column prop="name" label="规则名称" min-width="160">
          <template #default="{ row }">
            <div class="rule-name">
              <el-icon :class="getRuleTypeIconClass(row.rule_type)">
                <component :is="getRuleTypeIcon(row.rule_type)" />
              </el-icon>
              <span>{{ row.name }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="code" label="规则编码" width="180" />
        <el-table-column prop="rule_type" label="规则类型" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="getRuleTypeTag(row.rule_type)" size="small">
              {{ getRuleTypeLabel(row.rule_type) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="model_class" label="目标模型" width="200">
          <template #default="{ row }">
            <el-tooltip :content="row.model_class" placement="top">
              <span class="model-class">{{ getModelLabel(row.model_class) }}</span>
            </el-tooltip>
          </template>
        </el-table-column>
        <el-table-column prop="scope" label="作用域" width="100" align="center">
          <template #default="{ row }">
            <el-tag type="info" size="small">{{ getScopeLabel(row.scope) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="priority" label="优先级" width="80" align="center">
          <template #default="{ row }">
            <el-tag :type="row.priority >= 100 ? 'danger' : 'primary'" size="small">
              {{ row.priority }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="is_enabled" label="状态" width="80" align="center">
          <template #default="{ row }">
            <el-switch
              v-model="row.is_enabled"
              @change="handleToggle(row)"
              active-text="启用"
              inactive-text="禁用"
            />
          </template>
        </el-table-column>
        <el-table-column prop="description" label="描述" min-width="200" show-overflow-tooltip />
        <el-table-column label="操作" width="200" align="center" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleView(row)">
              查看
            </el-button>
            <el-button type="primary" link size="small" @click="handleEdit(row)">
              编辑
            </el-button>
            <el-button type="warning" link size="small" @click="handleTest(row)">
              测试
            </el-button>
            <el-button type="danger" link size="small" @click="handleDelete(row)">
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <el-pagination
          v-model:current-page="queryParams.page"
          v-model:page-size="queryParams.per_page"
          :page-sizes="[10, 15, 20, 50]"
          :total="total"
          layout="total, sizes, prev, pager, next, jumper"
          background
          @size-change="fetchData"
          @current-change="fetchData"
        />
      </div>
    </el-card>

    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="720px"
      :close-on-click-modal="false"
      class="rule-dialog"
    >
      <el-form
        ref="formRef"
        :model="formData"
        :rules="formRules"
        label-width="120px"
      >
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="规则名称" prop="name">
              <el-input v-model="formData.name" placeholder="请输入规则名称" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="规则编码" prop="code">
              <el-input v-model="formData.code" placeholder="请输入规则编码" :disabled="isEdit" />
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="规则类型" prop="rule_type">
              <el-select v-model="formData.rule_type" placeholder="请选择规则类型" style="width: 100%">
                <el-option label="租户隔离" value="tenant">
                  <span>租户隔离</span>
                  <span style="float: right; color: #8492a6; font-size: 13px">按租户ID隔离</span>
                </el-option>
                <el-option label="角色隔离" value="role">
                  <span>角色隔离</span>
                  <span style="float: right; color: #8492a6; font-size: 13px">按用户角色隔离</span>
                </el-option>
                <el-option label="自定义" value="custom">
                  <span>自定义</span>
                  <span style="float: right; color: #8492a6; font-size: 13px">自定义条件表达式</span>
                </el-option>
                <el-option label="字段隔离" value="field">
                  <span>字段隔离</span>
                  <span style="float: right; color: #8492a6; font-size: 13px">按指定字段值隔离</span>
                </el-option>
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="目标模型" prop="model_class">
              <el-select v-model="formData.model_class" placeholder="请选择目标模型" style="width: 100%">
                <el-option
                  v-for="item in modelClasses"
                  :key="item.value"
                  :label="item.label"
                  :value="item.value"
                />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="作用域" prop="scope">
              <el-select v-model="formData.scope" placeholder="请选择作用域" style="width: 100%">
                <el-option label="全局" value="global" />
                <el-option label="租户" value="tenant" />
                <el-option label="角色" value="role" />
                <el-option label="用户" value="user" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="优先级" prop="priority">
              <el-input-number v-model="formData.priority" :min="0" :max="1000" style="width: 100%" />
            </el-form-item>
          </el-col>
        </el-row>

        <el-form-item label="条件表达式" prop="condition_expression">
          <el-input
            v-model="formData.condition_expression"
            type="textarea"
            :rows="3"
            placeholder="输入过滤条件表达式，支持占位符：{user_id}、{tenant_id}、{user_type}"
          />
          <div class="expression-tip">
            <el-tag size="small" type="info">{user_id}</el-tag>
            <el-tag size="small" type="info">{tenant_id}</el-tag>
            <el-tag size="small" type="info">{user_type}</el-tag>
            <span class="tip-text">支持的变量占位符，运行时会自动替换为当前用户对应的值</span>
          </div>
        </el-form-item>

        <el-form-item label="字段映射" prop="field_mapping">
          <div class="field-mapping">
            <div v-for="(value, key) in formData.field_mapping" :key="key" class="mapping-item">
              <el-input v-model="key" placeholder="字段名" style="width: 150px" disabled />
              <span class="mapping-arrow">→</span>
              <el-input v-model="formData.field_mapping[key]" placeholder="映射值" style="width: 200px" />
              <el-button type="danger" link @click="removeFieldMapping(key)">
                <el-icon><Delete /></el-icon>
              </el-button>
            </div>
            <el-button type="primary" plain size="small" @click="addFieldMapping">
              <el-icon><Plus /></el-icon>添加字段映射
            </el-button>
          </div>
        </el-form-item>

        <el-form-item label="规则参数" prop="params">
          <div class="params-editor">
            <div v-for="(value, key) in formData.params" :key="key" class="param-item">
              <el-input v-model="key" placeholder="参数名" style="width: 150px" />
              <span class="param-sep">:</span>
              <el-input v-model="formData.params[key]" placeholder="参数值" style="width: 200px" />
              <el-button type="danger" link @click="removeParam(key)">
                <el-icon><Delete /></el-icon>
              </el-button>
            </div>
            <el-button type="primary" plain size="small" @click="addParam">
              <el-icon><Plus /></el-icon>添加参数
            </el-button>
          </div>
        </el-form-item>

        <el-form-item label="是否启用" prop="is_enabled">
          <el-switch v-model="formData.is_enabled" active-text="启用" inactive-text="禁用" />
        </el-form-item>

        <el-form-item label="规则描述" prop="description">
          <el-input
            v-model="formData.description"
            type="textarea"
            :rows="2"
            placeholder="请输入规则描述"
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="warning" @click="handleTestCurrent">
          <el-icon><DataLine /></el-icon>测试规则
        </el-button>
        <el-button type="primary" :loading="submitLoading" @click="handleSubmit">
          确定
        </el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="testDialogVisible" title="规则测试结果" width="600px">
      <div v-if="testResult" class="test-result">
        <el-result :icon="testResult.success ? 'success' : 'error'" :title="testResult.title">
          <template #sub-title>
            {{ testResult.message }}
          </template>
        </el-result>

        <el-descriptions v-if="testResult.data" :column="1" border class="result-detail">
          <el-descriptions-item label="SQL语句">
            <code class="sql-code">{{ testResult.data.sql }}</code>
          </el-descriptions-item>
          <el-descriptions-item label="绑定参数">
            <code>{{ JSON.stringify(testResult.data.bindings) }}</code>
          </el-descriptions-item>
          <el-descriptions-item label="影响数据量">
            <el-tag type="primary" size="large">{{ testResult.data.affected_count }}</el-tag>
          </el-descriptions-item>
        </el-descriptions>
      </div>
    </el-dialog>

    <el-drawer v-model="detailVisible" title="规则详情" size="500px">
      <div v-if="currentRule" class="rule-detail">
        <el-descriptions :column="1" border>
          <el-descriptions-item label="规则名称">{{ currentRule.name }}</el-descriptions-item>
          <el-descriptions-item label="规则编码">{{ currentRule.code }}</el-descriptions-item>
          <el-descriptions-item label="规则类型">
            <el-tag :type="getRuleTypeTag(currentRule.rule_type)">
              {{ getRuleTypeLabel(currentRule.rule_type) }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="目标模型">{{ getModelLabel(currentRule.model_class) }}</el-descriptions-item>
          <el-descriptions-item label="作用域">{{ getScopeLabel(currentRule.scope) }}</el-descriptions-item>
          <el-descriptions-item label="优先级">{{ currentRule.priority }}</el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag :type="currentRule.is_enabled ? 'success' : 'info'">
              {{ currentRule.is_enabled ? '已启用' : '已禁用' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="条件表达式">
            <code class="expression-code">{{ currentRule.condition_expression }}</code>
          </el-descriptions-item>
          <el-descriptions-item label="字段映射">
            <div v-if="currentRule.field_mapping && Object.keys(currentRule.field_mapping).length">
              <div v-for="(value, key) in currentRule.field_mapping" :key="key" class="mapping-detail">
                <code>{{ key }}</code> → <code>{{ value }}</code>
              </div>
            </div>
            <span v-else>-</span>
          </el-descriptions-item>
          <el-descriptions-item label="规则参数">
            <div v-if="currentRule.params && Object.keys(currentRule.params).length">
              <div v-for="(value, key) in currentRule.params" :key="key" class="param-detail">
                <code>{{ key }}</code>: <code>{{ value }}</code>
              </div>
            </div>
            <span v-else>-</span>
          </el-descriptions-item>
          <el-descriptions-item label="描述">{{ currentRule.description || '-' }}</el-descriptions-item>
        </el-descriptions>
      </div>
    </el-drawer>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getDataIsolationRules,
  createDataIsolationRule,
  updateDataIsolationRule,
  deleteDataIsolationRule,
  toggleDataIsolationRule,
  getModelClasses,
  testRule
} from '@/api/dataIsolation'

const loading = ref(false)
const submitLoading = ref(false)
const dialogVisible = ref(false)
const dialogTitle = ref('')
const formRef = ref(null)
const tableData = ref([])
const total = ref(0)
const isEdit = ref(false)
const modelClasses = ref([])
const testDialogVisible = ref(false)
const testResult = ref(null)
const detailVisible = ref(false)
const currentRule = ref(null)

const stats = ref({
  total: 0,
  enabled: 0,
  disabled: 0,
  modelCount: 0
})

const queryParams = reactive({
  page: 1,
  per_page: 15,
  keyword: '',
  rule_type: '',
  model_class: '',
  is_enabled: ''
})

const formData = reactive({
  id: null,
  name: '',
  code: '',
  model_class: '',
  rule_type: '',
  scope: 'tenant',
  condition_expression: '',
  params: {},
  field_mapping: {},
  priority: 0,
  is_enabled: true,
  description: ''
})

const formRules = {
  name: [{ required: true, message: '请输入规则名称', trigger: 'blur' }],
  code: [
    { required: true, message: '请输入规则编码', trigger: 'blur' },
    { pattern: /^[a-zA-Z_][a-zA-Z0-9_]*$/, message: '编码只能包含字母、数字和下划线', trigger: 'blur' }
  ],
  model_class: [{ required: true, message: '请选择目标模型', trigger: 'change' }],
  rule_type: [{ required: true, message: '请选择规则类型', trigger: 'change' }],
  scope: [{ required: true, message: '请选择作用域', trigger: 'change' }],
  condition_expression: [{ required: true, message: '请输入条件表达式', trigger: 'blur' }]
}

const ruleTypeIcons = {
  tenant: 'OfficeBuilding',
  role: 'UserFilled',
  custom: 'Setting',
  field: 'Collection'
}

const ruleTypeTags = {
  tenant: 'warning',
  role: 'primary',
  custom: 'success',
  field: 'info'
}

const ruleTypeLabels = {
  tenant: '租户隔离',
  role: '角色隔离',
  custom: '自定义',
  field: '字段隔离'
}

const scopeLabels = {
  global: '全局',
  tenant: '租户',
  role: '角色',
  user: '用户'
}

function getRuleTypeIcon(type) {
  return ruleTypeIcons[type] || 'Setting'
}

function getRuleTypeIconClass(type) {
  return `rule-icon ${type}`
}

function getRuleTypeTag(type) {
  return ruleTypeTags[type] || 'info'
}

function getRuleTypeLabel(type) {
  return ruleTypeLabels[type] || type
}

function getScopeLabel(scope) {
  return scopeLabels[scope] || scope
}

function getModelLabel(modelClass) {
  const model = modelClasses.value.find((m) => m.value === modelClass)
  return model ? model.label : modelClass
}

async function fetchModelClasses() {
  try {
    const res = await getModelClasses()
    modelClasses.value = res.data
  } catch (error) {
    console.error('获取模型列表失败:', error)
  }
}

async function fetchData() {
  loading.value = true
  try {
    const params = { ...queryParams }
    if (params.is_enabled === '') {
      delete params.is_enabled
    }

    const res = await getDataIsolationRules(params)
    tableData.value = res.data.data
    total.value = res.data.total

    stats.value = {
      total: res.data.total,
      enabled: tableData.value.filter((r) => r.is_enabled).length,
      disabled: tableData.value.filter((r) => !r.is_enabled).length,
      modelCount: new Set(tableData.value.map((r) => r.model_class)).size
    }
  } catch (error) {
    console.error('获取规则列表失败:', error)
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  queryParams.page = 1
  fetchData()
}

function handleReset() {
  queryParams.keyword = ''
  queryParams.rule_type = ''
  queryParams.model_class = ''
  queryParams.is_enabled = ''
  queryParams.page = 1
  fetchData()
}

function handleAdd() {
  isEdit.value = false
  dialogTitle.value = '新建数据隔离规则'
  Object.assign(formData, {
    id: null,
    name: '',
    code: '',
    model_class: '',
    rule_type: '',
    scope: 'tenant',
    condition_expression: '',
    params: {},
    field_mapping: {},
    priority: 0,
    is_enabled: true,
    description: ''
  })
  dialogVisible.value = true
}

function handleEdit(row) {
  isEdit.value = true
  dialogTitle.value = '编辑数据隔离规则'
  Object.assign(formData, {
    id: row.id,
    name: row.name,
    code: row.code,
    model_class: row.model_class,
    rule_type: row.rule_type,
    scope: row.scope,
    condition_expression: row.condition_expression,
    params: { ...(row.params || {}) },
    field_mapping: { ...(row.field_mapping || {}) },
    priority: row.priority,
    is_enabled: row.is_enabled,
    description: row.description || ''
  })
  dialogVisible.value = true
}

function handleView(row) {
  currentRule.value = row
  detailVisible.value = true
}

async function handleSubmit() {
  if (!formRef.value) return

  try {
    await formRef.value.validate()
    submitLoading.value = true

    if (isEdit.value) {
      await updateDataIsolationRule(formData.id, formData)
      ElMessage.success('更新成功')
    } else {
      await createDataIsolationRule(formData)
      ElMessage.success('创建成功')
    }

    dialogVisible.value = false
    fetchData()
  } catch (error) {
    console.error('提交失败:', error)
  } finally {
    submitLoading.value = false
  }
}

async function handleToggle(row) {
  try {
    await toggleDataIsolationRule(row.id)
    ElMessage.success(row.is_enabled ? '已启用' : '已禁用')
  } catch (error) {
    console.error('切换状态失败:', error)
    row.is_enabled = !row.is_enabled
  }
}

async function handleDelete(row) {
  ElMessageBox.confirm(`确定要删除规则"${row.name}"吗？删除后将立即失效。`, '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning',
    confirmButtonClass: 'el-button--danger'
  }).then(async () => {
    try {
      await deleteDataIsolationRule(row.id)
      ElMessage.success('删除成功')
      fetchData()
    } catch (error) {
      console.error('删除失败:', error)
    }
  })
}

async function handleTest(row) {
  testDialogVisible.value = true
  testResult.value = null

  try {
    const res = await testRule({
      model_class: row.model_class,
      rule_type: row.rule_type,
      condition_expression: row.condition_expression,
      params: row.params,
      field_mapping: row.field_mapping
    })

    testResult.value = {
      success: true,
      title: '测试成功',
      message: '规则执行成功，以下是执行结果详情',
      data: res.data
    }
  } catch (error) {
    testResult.value = {
      success: false,
      title: '测试失败',
      message: error.message || '规则执行失败'
    }
  }
}

function handleTestCurrent() {
  handleTest(formData)
}

function addFieldMapping() {
  const key = `field_${Object.keys(formData.field_mapping).length + 1}`
  formData.field_mapping[key] = ''
}

function removeFieldMapping(key) {
  delete formData.field_mapping[key]
}

function addParam() {
  const key = `param_${Object.keys(formData.params).length + 1}`
  formData.params[key] = ''
}

function removeParam(key) {
  delete formData.params[key]
}

onMounted(() => {
  fetchModelClasses()
  fetchData()
})
</script>

<style scoped lang="scss">
.data-isolation-page {
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 20px;

    .page-title {
      font-size: 20px;
      color: $text-primary;
      margin: 0 0 4px 0;
    }

    .page-desc {
      font-size: 13px;
      color: $text-secondary;
      margin: 0;
    }
  }

  .stats-cards {
    margin-bottom: 20px;
  }

  .stat-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);

    .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      color: #fff;

      &.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
      &.success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
      &.warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
      &.info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    }

    .stat-content {
      .stat-value {
        font-size: 24px;
        font-weight: 600;
        color: $text-primary;
        line-height: 1.2;
      }

      .stat-label {
        font-size: 13px;
        color: $text-secondary;
        margin-top: 4px;
      }
    }
  }

  .filter-card {
    margin-bottom: 20px;
  }

  .table-card {
    .rule-name {
      display: flex;
      align-items: center;
      gap: 8px;

      .rule-icon {
        font-size: 18px;

        &.tenant { color: #e6a23c; }
        &.role { color: #409eff; }
        &.custom { color: #67c23a; }
        &.field { color: #909399; }
      }
    }

    .model-class {
      font-family: monospace;
      font-size: 12px;
      color: $text-regular;
    }
  }

  .pagination {
    margin-top: 20px;
    display: flex;
    justify-content: flex-end;
  }

  .expression-tip {
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 8px;

    .tip-text {
      font-size: 12px;
      color: $text-secondary;
    }
  }

  .field-mapping,
  .params-editor {
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: 100%;

    .mapping-item,
    .param-item {
      display: flex;
      align-items: center;
      gap: 10px;

      .mapping-arrow,
      .param-sep {
        color: $text-secondary;
      }
    }
  }

  .rule-dialog {
    :deep(.el-dialog__body) {
      max-height: 60vh;
      overflow-y: auto;
    }
  }

  .test-result {
    .sql-code {
      display: block;
      padding: 10px;
      background: #f5f7fa;
      border-radius: 4px;
      font-size: 12px;
      word-break: break-all;
    }

    .result-detail {
      margin-top: 20px;
    }
  }

  .rule-detail {
    .expression-code {
      display: block;
      padding: 10px;
      background: #f5f7fa;
      border-radius: 4px;
      font-size: 12px;
      word-break: break-all;
    }

    .mapping-detail,
    .param-detail {
      margin-bottom: 4px;

      code {
        background: #f5f7fa;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 12px;
      }
    }
  }
}
</style>
