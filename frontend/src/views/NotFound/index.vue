<template>
  <div class="not-found">
    <div class="content">
      <div class="code">404</div>
      <div class="message">抱歉，您访问的页面不存在</div>
      <div class="actions">
        <el-button type="primary" @click="goHome">
          <el-icon><House /></el-icon>返回首页
        </el-button>
        <el-button @click="goBack">
          <el-icon><Back /></el-icon>返回上一页
        </el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

function goHome() {
  router.push('/')
}

function goBack() {
  router.back()
}
</script>

<style scoped lang="scss">
.not-found {
  width: 100%;
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

  .content {
    text-align: center;
    color: #fff;

    .code {
      font-size: 120px;
      font-weight: bold;
      text-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }

    .message {
      font-size: 20px;
      margin-bottom: 30px;
      opacity: 0.9;
    }

    .actions {
      display: flex;
      gap: 16px;
      justify-content: center;
    }
  }
}
</style>
