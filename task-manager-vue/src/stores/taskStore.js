import { defineStore } from "pinia";
import { ref } from "vue";
import apiClient from "../services/axiosClient";
import cogoToast from "cogo-toast";
import { useRouter } from "vue-router";
import { useAuthStore } from "./authStore";

export const useTaskStore = defineStore("task", () => {
  const authStore = useAuthStore();
  const getTasks = async () => {
    try {
      const res = await apiClient.get("/tasks");
      return res.data.data.data;
    } catch (error) {
      cogoToast.error("Internal Server Error", { position: "top-right" });
      return [];
    }
  };

  const getTrashedTasks = async () => {
    try {
      const res = await apiClient.get("/trashed-tasks");
      return res.data.data.data;
    } catch (error) {
      cogoToast.error("Failed to load trashed tasks", {
        position: "top-right",
      });
      return [];
    }
  };

  const createTask = async (taskData) => {
    try {
      await apiClient.post("/tasks", taskData);
      cogoToast.success("Task Created Successful", { position: "top-right" });
      return true;
    } catch (error) {
      cogoToast.error("Internal Server Error", { position: "top-right" });
      return false;
    }
  };

  const updateTask = async (id, taskData) => {
    try {
      await apiClient.put(`/tasks/${id}`, taskData);
      cogoToast.success("Task Updated Successful", { position: "top-right" });
      return true;
    } catch (error) {
      cogoToast.error("Failed to Update Task", { position: "top-right" });
      return false;
    }
  };

  const deleteTask = async (id) => {
    try {
      await apiClient.delete(`/tasks/${id}`);
      cogoToast.success("Task Moved to Trash", { position: "top-right" });
      return true;
    } catch (error) {
      cogoToast.error("Failed to Delete Task", { position: "top-right" });
      return false;
    }
  };

  const restoreTask = async (taskId) => {
    try {
      await apiClient.patch(`/tasks/${taskId}/restore`);
      cogoToast.success("Task Restored Successful", { position: "top-right" });
      return true;
    } catch (error) {
      cogoToast.error("Failed to Restore Task", { position: "top-right" });
      return false;
    }
  };

  const forceDeleteTask = async (taskId) => {
    try {
      await apiClient.delete(`/tasks/${taskId}/force-delete`);
      cogoToast.success("Task Permanently Deleted", { position: "top-right" });
      return true;
    } catch (error) {
      cogoToast.error("Failed to Permanently Delete Task", {
        position: "top-right",
      });
      return false;
    }
  };

  const updateTaskStatus = async (taskId, status, currentTask = null) => {
  try {
    if (currentTask) {
      if (!authStore.isAdmin) {
        const allowedStatuses = ["progress", "hold", "completed"];
        
        const isAssignedToUser =
          Array.isArray(currentTask.assigned_users) &&
          currentTask.assigned_users.some(
            (u) => Number(u.id) === Number(authStore.user?.id)
          );

        if (!isAssignedToUser) {
          cogoToast.warn("You can only update tasks assigned to you", {
            position: "top-right",
          });
          return null;
        }

        if (!allowedStatuses.includes(status)) {
          cogoToast.warn(
            "You can only change status to Progress, Hold, or Completed",
            { position: "top-right" }
          );
          return null;
        }
      }
    }

    const res = await apiClient.patch(`/tasks/${taskId}/status`, { status });
    cogoToast.success("Task Status Updated Successfully", {
      position: "top-right",
    });
    return res.data.data;
  } catch (error) {
    console.error(error);
    cogoToast.error("Failed to Update Task Status", {
      position: "top-right",
    });
    return null;
  }
};


  return {
    getTasks,
    createTask,
    updateTask,
    deleteTask,
    getTrashedTasks,
    restoreTask,
    forceDeleteTask,
    updateTaskStatus,
  };
});
