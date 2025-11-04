import { createRouter, createWebHistory } from "vue-router";
import RegistrationPage from "./pages/RegistrationPage.vue";
import LoginPage from "./pages/LoginPage.vue";
import DashboardPage from "./pages/DashboardPage.vue";
import MasterLayout from "./components/MasterLayout.vue";
import TaskList from "./pages/TaskList.vue";
import TaskCreate from "./pages/TaskCreate.vue";
import ProfilePage from "./pages/ProfilePage.vue";
import GroupList from "./pages/GroupList.vue";
import GroupCreate from "./pages/GroupCreate.vue";
import TaskAssign from "./pages/TaskAssign.vue";
import GroupUser from "./pages/GroupUser.vue";
import { useAuthStore } from "./stores/authStore";
import cogoToast from "cogo-toast";
import UserList from "./pages/UserList.vue";

const routes = [
  {
    path: "/register",
    component: RegistrationPage,
    name: "register",
    meta: { requiresGuest: true },
  },
  {
    path: "/",
    redirect: "/login",
  },
  {
    path: "/login",
    component: LoginPage,
    name: "login",
    meta: { requiresGuest: true },
  },
  {
    path: "/",
    component: MasterLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: "/dashboard",
        component: DashboardPage,
        name: "dashboard",
      },
      {
        path: "/users",
        component: UserList,
        name: "users",
      },
      {
        path: "/profile",
        component: ProfilePage,
        name: "profile",
      },
      {
        path: "/tasks",
        component: TaskList,
        name: "tasks",
      },
      {
        path: "/tasks/create",
        component: TaskCreate,
        name: "task-create",
        meta: { requiresAuth: true, type: "Admin" },
      },
      {
        path: "/tasks/:id/assign",
        component: TaskAssign,
        name: "task-assign",
        props: true,
        meta: { requiresAuth: true, type: "Admin" },
      },
      {
        path: "/groups",
        component: GroupList,
        name: "groups",
      },
      {
        path: "/groups/create",
        component: GroupCreate,
        name: "group-create",
        meta: { requiresAuth: true, type: "Admin" },
      },
      {
        path: "/groups/:id/users",
        component: GroupUser,
        name: "group-users",
        props: true,
        meta: { requiresAuth: true, type: "Admin" },
      },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Global Route Guard
router.beforeEach(async (to, from, next) => {
  const token = localStorage.getItem("token");
  const authStore = useAuthStore();

  if (token && !authStore.user) {
    try {
      await authStore.getProfile();
    } catch {
      localStorage.removeItem("token");
      return next({ name: "login" });
    }
  }

  if (to.meta.requiresAuth && !token) {
    return next({ name: "login" });
  }

  if (to.meta.requiresGuest && token) {
    return next({ name: "dashboard" });
  }

  if (to.meta.type && authStore.user?.type !== to.meta.type) {
    cogoToast.error("Access Denied", { position: "top-right" });
    return next({ name: "dashboard" });
  }

  next();
});

export default router;
