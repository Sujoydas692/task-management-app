import { defineStore } from "pinia";
import { ref, computed } from "vue";
import apiClient from "../services/axiosClient";
import cogoToast from "cogo-toast";
import { useRouter } from "vue-router";

export const useAuthStore = defineStore("auth", () => {
  const router = useRouter();
  const user = ref(null);
  const token = ref(localStorage.getItem("token") || null);

  const isAuthenticated = computed(() => !!token.value);
  const isAdmin = computed(() => user.value?.type === "Admin");

  // Registration
  const register = async (formData) => {
    try {
      await apiClient.post("/auth/register", formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      });
      cogoToast.success("Registration Successful", { position: "top-right" });
      return true;
    } catch (error) {
      if (error.status === 422) {
        const errors = error.response.data.messages;
        errors.forEach((msg) => {
          cogoToast.error(msg, { position: "top-right" });
        });
      } else {
        cogoToast.error("Internal Server Error.", { position: "top-right" });
      }
      return false;
    }
  };

  const login = async (credentials) => {
    try {
      const res = await apiClient.post("/auth/login", credentials);
      if (res.status === 200 && res.data?.data?.token) {
        token.value = res.data.data.token;
        user.value = res.data.data.user;

        localStorage.setItem("token", token.value);

        cogoToast.success("Login Successful", { position: "top-right" });
        return true;
      } else {
        cogoToast.error("Unexpected response from server.", {
          position: "top-right",
        });
        return false;
      }
    } catch (error) {
      if (error.response?.status == 422) {
        const errors = error.response.data.messages;
        errors.forEach((msg) => {
          cogoToast.error(msg, { position: "top-right" });
        });
      } else {
        cogoToast.error("Something went wrong", { position: "top-right" });
      }
    }
  };

  const logout = async () => {
    try {
      const response = await apiClient.post("/auth/logout");

      if (response.status === 200 || response.status === 204) {
        token.value = null;
        user.value = null;
        localStorage.removeItem("token");

        cogoToast.success("Logout Successful", { position: "top-right" });
        router.push({ name: "login" });
        return true;
      } else {
        cogoToast.error("Unexpected logout response", {
          position: "top-right",
        });
        return false;
      }
    } catch (error) {
      cogoToast.error("Internal Server Error", { position: "top-right" });
      return false;
    }
  };

  const getProfile = async () => {
    if (!token.value) return;
    try {
      const res = await apiClient.get("/auth/user", {
        headers: { Authorization: `Bearer ${token.value}` },
      });
      user.value = res.data.data;
      return res.data.data;
    } catch (error) {
      cogoToast.error("Something went wrong", { position: "top-right" });
      return false;
    }
  };

  const updateProfile = async (formData) => {
      try {
        const res = await apiClient.post('/auth/user?_method=PUT', formData, {
          headers: {
            Authorization: `Bearer ${token.value}`,
            'Content-Type': 'multipart/form-data'
          }
        })
        user.value = res.data.data
        cogoToast.success('Profile updated successfully', { position: 'top-right' })
        return res.data.data
      } catch (error) {
        cogoToast.error('Update failed', { position: 'top-right' })
        console.error(error)
      }
    }

  return {
    router,
    user,
    token,
    isAuthenticated,
    isAdmin,
    register,
    login,
    logout,
    getProfile,
    updateProfile,
  };
});
