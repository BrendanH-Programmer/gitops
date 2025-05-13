provider "azurerm" {
  client_id       = var.client_id
  client_secret   = var.client_secret
  tenant_id       = var.tenant_id
  subscription_id = var.subscription_id

  features {}  # Required block even if empty
}

resource "azurerm_resource_group" "example" {
  name     = "example-resources-new"  # <--- changed name
  location = "UK South"
}

resource "azurerm_virtual_network" "example" {
  name                = "example-vnet"
  address_space       = ["10.0.0.0/16"]
  location            = azurerm_resource_group.example.location
  resource_group_name = azurerm_resource_group.example.name
}

output "resource_group_name" {
  value = azurerm_resource_group.example.name
}

variable "resource_group_name" {
  description = "The name of the resource group"
  type        = string
  default     = "example-resources"
}
