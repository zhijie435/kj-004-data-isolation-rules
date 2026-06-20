import request from '@/utils/request'

const BASE_URL = '/api/v1/data-isolation'

export function getDataIsolationRules(params) {
  return request({
    url: `${BASE_URL}/rules`,
    method: 'get',
    params
  })
}

export function getDataIsolationRule(id) {
  return request({
    url: `${BASE_URL}/rules/${id}`,
    method: 'get'
  })
}

export function createDataIsolationRule(data) {
  return request({
    url: `${BASE_URL}/rules`,
    method: 'post',
    data
  })
}

export function updateDataIsolationRule(id, data) {
  return request({
    url: `${BASE_URL}/rules/${id}`,
    method: 'put',
    data
  })
}

export function deleteDataIsolationRule(id) {
  return request({
    url: `${BASE_URL}/rules/${id}`,
    method: 'delete'
  })
}

export function toggleDataIsolationRule(id, is_enabled) {
  return request({
    url: `${BASE_URL}/rules/${id}/toggle`,
    method: 'post',
    data: { is_enabled }
  })
}

export function getRuleTypes() {
  return request({
    url: `${BASE_URL}/rule-types`,
    method: 'get'
  })
}

export function getModelClasses() {
  return request({
    url: `${BASE_URL}/model-classes`,
    method: 'get'
  })
}

export function testRule(data) {
  return request({
    url: `${BASE_URL}/test-rule`,
    method: 'post',
    data
  })
}
