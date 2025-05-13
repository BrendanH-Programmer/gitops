provider "azurerm" {
  client_id       = var.client_id
  client_secret   = var.client_secret
  tenant_id       = var.tenant_id
  subscription_id = var.subscription_id
  features {}
}

variable "resource_group_name" {
  description = "The name of the resource group"
  type        = string
  default     = "example-resources"  # Default value, or you can specify during apply
}

resource "azurerm_virtual_network" "example" {
  name                = "example-vnet"
  location            = "UK SOUTH"
  resource_group_name = var.resource_group_name  # Dynamically refer to resource group
  address_space       = ["10.0.0.0/16"]
}