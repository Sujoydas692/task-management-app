import { defineStore } from "pinia";
import apiClient from "../services/axiosClient";
import cogoToast from "cogo-toast";

export const useTaskAssignStore = defineStore("taskAssign", () => {
  const getGroups = async () => {
    try {
      const res = await apiClient.get("/groups");
      return res.data.data.data;
    } catch (error) {
      cogoToast.error("Failed to load groups", { position: "top-right" });
      return [];
    }
  };

  const getGroupUsers = async (groupId) => {
    try {
      const res = await apiClient.get(`/groups/${groupId}/users`);
      return res.data.data.data; // paginated data
    } catch (error) {
      cogoToast.error("Failed to load users", { position: "top-right" });
      return [];
    }
  };

  const getAllUsers = async () => {
    try {
      const res = await apiClient.get("/auth/users");
      return res.data.data || [];
    } catch (error) {
      cogoToast.error("Failed to load all users", { position: "top-right" });
      return [];
    }
  };

  const assignTask = async (taskId, payload) => {
    try {
      await apiClient.post(`/tasks/${taskId}/assignments`, payload);
      cogoToast.success("Task Assigned Successful", { position: "top-right" });
      return true;
    } catch (error) {
      cogoToast.error("Failed to assign task", { position: "top-right" });
      return false;
    }
  };

  const getAssignments = async (taskId) => {
    try {
      const res = await apiClient.get(`/tasks/${taskId}/assignments`);
      return res.data.data.data;
    } catch (err) {
      cogoToast.error("Failed to load assignments", { position: "top-right" });
      return [];
    }
  };

  const deleteAssignment = async (taskId, assignmentId) => {
    try {
      await apiClient.delete(`/tasks/${taskId}/assignments/${assignmentId}`);
      cogoToast.success("Assignment Remove Successful", {
        position: "top-right",
      });
      return true;
    } catch (error) {
      cogoToast.error("Failed to delete assignment", { position: "top-right" });
      return false;
    }
  };

  return {
    getGroups,
    getGroupUsers,
    assignTask,
    getAssignments,
    deleteAssignment,
    getAllUsers,
  };
});
