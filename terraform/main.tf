provider "azurerm" {
  client_id       = var.client_id
  client_secret   = var.client_secret
  tenant_id       = var.tenant_id
  subscription_id = var.subscription_id

  features {}  # Required block even if empty
}

resource "azurerm_resource_group" "example" {
  name     = "example-resources-new10"  # <--- changed name
  location = "UK South"
}

variable "resource_group_name" {
  description = "The name of the resource group"
  type        = string
  default     = "example-resources"
}
